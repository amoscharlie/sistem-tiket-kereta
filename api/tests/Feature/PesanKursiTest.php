<?php

namespace Tests\Feature;

use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Kursi;
use App\Models\User;
use Database\Seeders\TiketSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PesanKursiTest extends TestCase
{
    use RefreshDatabase;

    public function test_peran_admin_tidak_bisa_diisi_saat_daftar(): void
    {
        $this->postJson('/api/v1/daftar', [
            'name' => 'Amos',
            'email' => 'amos@example.com',
            'telepon' => '081111111111',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'admin',
        ])->assertCreated()
            ->assertJsonPath('user.role', 'pelanggan');
    }

    public function test_kursi_yang_sama_tidak_bisa_dipesan_dua_kali(): void
    {
        $this->seed(TiketSeeder::class);

        $kelas = Kelas::query()->where('nama', 'Ekonomi')->firstOrFail();
        $jadwalId = Kursi::query()->where('kelas_id', $kelas->id)->value('jadwal_id');
        $kursiId = Kursi::query()->where('jadwal_id', $jadwalId)->where('kelas_id', $kelas->id)->value('id');

        $pertama = User::query()->where('email', 'pelanggan@tiket.test')->firstOrFail();
        $kedua = User::factory()->create(['role' => 'pelanggan']);

        $payload = [
            'jadwal_id' => $jadwalId,
            'kelas_id' => $kelas->id,
            'penumpang' => [
                ['kursi_id' => $kursiId, 'nama' => 'Sari', 'umur' => 28, 'nik' => '3201010101900001'],
            ],
        ];

        $this->actingAs($pertama, 'sanctum')->postJson('/api/v1/pesanan', $payload)->assertCreated();
        $this->actingAs($kedua, 'sanctum')->postJson('/api/v1/pesanan', $payload)->assertStatus(409);
    }

    public function test_admin_menyetujui_bukti_manual(): void
    {
        Storage::fake('public');
        $this->seed(TiketSeeder::class);

        $pelanggan = User::query()->where('email', 'pelanggan@tiket.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@tiket.test')->firstOrFail();
        $kelas = Kelas::query()->where('nama', 'Eksekutif')->firstOrFail();
        $kursi = Kursi::query()->where('kelas_id', $kelas->id)->firstOrFail();

        $buat = $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan', [
            'jadwal_id' => $kursi->jadwal_id,
            'kelas_id' => $kelas->id,
            'penumpang' => [
                ['kursi_id' => $kursi->id, 'nama' => 'Budi', 'umur' => 34, 'nik' => '3201010101900002'],
            ],
        ])->assertCreated();

        $id = $buat->json('data.id');

        $this->actingAs($pelanggan, 'sanctum')->post('/api/v1/pesanan/'.$id.'/bukti', [
            'bukti' => UploadedFile::fake()->image('bukti.jpg'),
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('data.status', 'menunggu_verifikasi');

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/pembayaran/'.$buat->json('data.pembayaran.id').'/setujui')
            ->assertOk()
            ->assertJsonPath('data.status', 'lunas');
    }

    public function test_bayi_di_bawah_tiga_tahun_tidak_dikenakan_tiket(): void
    {
        $this->seed(TiketSeeder::class);

        $pelanggan = User::query()->where('email', 'pelanggan@tiket.test')->firstOrFail();
        $kelas = Kelas::query()->where('nama', 'Ekonomi')->firstOrFail();
        $kursi = Kursi::query()->where('kelas_id', $kelas->id)->firstOrFail();
        $harga = JadwalKelas::query()
            ->where('jadwal_id', $kursi->jadwal_id)
            ->where('kelas_id', $kelas->id)
            ->value('harga');

        $buat = $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan', [
            'jadwal_id' => $kursi->jadwal_id,
            'kelas_id' => $kelas->id,
            'penumpang' => [
                ['kursi_id' => $kursi->id, 'nama' => 'Sari', 'umur' => 30, 'nik' => '3201010101900003'],
            ],
            'bayi' => [
                ['nama' => 'Ayah', 'umur' => 1],
            ],
        ])->assertCreated();

        $this->assertEquals((float) $harga, (float) $buat->json('data.total'));

        $bayi = collect($buat->json('data.penumpang'))->firstWhere('nama', 'Ayah');
        $this->assertTrue($bayi['gratis']);
        $this->assertNull($bayi['kursi']);
    }

    public function test_pelanggan_bisa_membatalkan_lalu_kursi_bisa_dipesan_lagi(): void
    {
        $this->seed(TiketSeeder::class);

        $pelanggan = User::query()->where('email', 'pelanggan@tiket.test')->firstOrFail();
        $kelas = Kelas::query()->where('nama', 'Ekonomi')->firstOrFail();
        $kursi = Kursi::query()->where('kelas_id', $kelas->id)->firstOrFail();
        $payload = [
            'jadwal_id' => $kursi->jadwal_id,
            'kelas_id' => $kelas->id,
            'penumpang' => [
                ['kursi_id' => $kursi->id, 'nama' => 'Sari', 'umur' => 28, 'nik' => '3201010101900004'],
            ],
        ];

        $buat = $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan', $payload)->assertCreated();
        $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan/'.$buat->json('data.id').'/batal')
            ->assertOk()
            ->assertJsonPath('data.status', 'batal');

        $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan', $payload)->assertCreated();
    }

    public function test_pintu_menolak_tiket_yang_belum_lunas(): void
    {
        $this->seed(TiketSeeder::class);

        $pelanggan = User::query()->where('email', 'pelanggan@tiket.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@tiket.test')->firstOrFail();
        $kelas = Kelas::query()->where('nama', 'Ekonomi')->firstOrFail();
        $kursi = Kursi::query()->where('kelas_id', $kelas->id)->firstOrFail();

        $buat = $this->actingAs($pelanggan, 'sanctum')->postJson('/api/v1/pesanan', [
            'jadwal_id' => $kursi->jadwal_id,
            'kelas_id' => $kelas->id,
            'penumpang' => [
                ['kursi_id' => $kursi->id, 'nama' => 'Sari', 'umur' => 28, 'nik' => '3201010101900005'],
            ],
        ])->assertCreated();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/admin/pintu', [
            'kode' => $buat->json('data.kode'),
        ])->assertStatus(422);
    }
}
