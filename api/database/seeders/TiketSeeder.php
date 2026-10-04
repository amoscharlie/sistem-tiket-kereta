<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Kereta;
use App\Models\Kursi;
use App\Models\Moda;
use App\Models\Stasiun;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TiketSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Tiket',
            'email' => 'admin@tiket.test',
            'telepon' => '081200000001',
            'password' => 'password',
            'role' => 'admin',
            'email_verified_at' => now(),
            'hp_verified_at' => now(),
            'whatsapp_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Pengelola Tiket',
            'email' => 'pengelola@tiket.test',
            'telepon' => '081200000002',
            'password' => 'password',
            'role' => 'pengelola',
            'email_verified_at' => now(),
            'hp_verified_at' => now(),
            'whatsapp_verified_at' => now(),
        ]);

        User::create([
            'name' => 'Pelanggan Contoh',
            'email' => 'pelanggan@tiket.test',
            'telepon' => '081234567890',
            'password' => 'password',
            'role' => 'pelanggan',
            'email_verified_at' => now(),
            'hp_verified_at' => now(),
            'whatsapp_verified_at' => now(),
        ]);

        $moda = Moda::create(['kode' => 'kereta', 'nama' => 'Kereta']);

        $stasiun = collect([
            ['kode' => 'GMR', 'nama' => 'Gambir', 'kota' => 'Jakarta'],
            ['kode' => 'BD', 'nama' => 'Bandung', 'kota' => 'Bandung'],
            ['kode' => 'YK', 'nama' => 'Yogyakarta', 'kota' => 'Yogyakarta'],
            ['kode' => 'SMT', 'nama' => 'Semarang Tawang', 'kota' => 'Semarang'],
            ['kode' => 'SGU', 'nama' => 'Surabaya Gubeng', 'kota' => 'Surabaya'],
        ])->mapWithKeys(fn (array $baris) => [$baris['kode'] => Stasiun::create($baris)]);

        $parahyangan = Kereta::create(['moda_id' => $moda->id, 'kode' => 'AP', 'nama' => 'Argo Parahyangan']);
        $taksaka = Kereta::create(['moda_id' => $moda->id, 'kode' => 'TK', 'nama' => 'Taksaka']);

        $ekonomi = Kelas::create(['nama' => 'Ekonomi', 'urutan' => 1]);
        $eksekutif = Kelas::create(['nama' => 'Eksekutif', 'urutan' => 2]);

        $hari = Carbon::now('Asia/Jakarta')->startOfDay();

        for ($i = 0; $i < 3; $i++) {
            $tanggal = $hari->copy()->addDays($i);

            $this->buatJadwal(
                $parahyangan,
                $stasiun['GMR'],
                $stasiun['BD'],
                $tanggal->copy()->setTime(8, 0),
                $tanggal->copy()->setTime(11, 5),
                [$ekonomi->id => 180000, $eksekutif->id => 320000],
            );

            $this->buatJadwal(
                $parahyangan,
                $stasiun['BD'],
                $stasiun['GMR'],
                $tanggal->copy()->setTime(15, 0),
                $tanggal->copy()->setTime(18, 10),
                [$ekonomi->id => 180000, $eksekutif->id => 320000],
            );

            $this->buatJadwal(
                $taksaka,
                $stasiun['GMR'],
                $stasiun['YK'],
                $tanggal->copy()->setTime(20, 0),
                $tanggal->copy()->addDay()->setTime(3, 30),
                [$ekonomi->id => 350000, $eksekutif->id => 650000],
            );
        }
    }

    private function buatJadwal(Kereta $kereta, Stasiun $asal, Stasiun $tujuan, Carbon $berangkat, Carbon $tiba, array $harga): void
    {
        $jadwal = new Jadwal;
        $jadwal->kereta_id = $kereta->id;
        $jadwal->stasiun_asal_id = $asal->id;
        $jadwal->stasiun_tujuan_id = $tujuan->id;
        $jadwal->berangkat_at = $berangkat;
        $jadwal->tiba_at = $tiba;
        $jadwal->save();

        foreach ($harga as $kelasId => $nominal) {
            $baris = new JadwalKelas;
            $baris->jadwal_id = $jadwal->id;
            $baris->kelas_id = $kelasId;
            $baris->harga = $nominal;
            $baris->save();

            foreach (range(1, 4) as $nomor) {
                foreach (['A', 'B', 'C', 'D'] as $kolom) {
                    $kursi = new Kursi;
                    $kursi->jadwal_id = $jadwal->id;
                    $kursi->kelas_id = $kelasId;
                    $kursi->kode = $nomor.$kolom;
                    $kursi->baris = $nomor;
                    $kursi->kolom = $kolom;
                    $kursi->save();
                }
            }
        }
    }
}
