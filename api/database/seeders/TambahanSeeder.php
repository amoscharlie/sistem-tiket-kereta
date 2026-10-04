<?php

namespace Database\Seeders;

use App\Models\Jadwal;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Kereta;
use App\Models\Moda;
use App\Models\Stasiun;
use App\Models\User;
use App\Models\Voucher;
use App\Services\KursiPabrik;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TambahanSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->whereNull('email_verified_at')->update([
            'email_verified_at' => now(),
            'hp_verified_at' => now(),
            'whatsapp_verified_at' => now(),
        ]);

        User::query()->updateOrCreate(
            ['email' => 'pengelola@tiket.test'],
            [
                'name' => 'Pengelola Tiket',
                'telepon' => '081200000002',
                'password' => 'password',
                'role' => 'pengelola',
                'email_verified_at' => now(),
                'hp_verified_at' => now(),
                'whatsapp_verified_at' => now(),
            ],
        );

        $moda = Moda::query()->firstOrCreate(['kode' => 'kereta'], ['nama' => 'Kereta']);

        $stasiun = collect([
            ['kode' => 'PSE', 'nama' => 'Pasar Senen', 'kota' => 'Jakarta'],
            ['kode' => 'JNG', 'nama' => 'Jatinegara', 'kota' => 'Jakarta'],
            ['kode' => 'CN', 'nama' => 'Cirebon', 'kota' => 'Cirebon'],
            ['kode' => 'PWT', 'nama' => 'Purwokerto', 'kota' => 'Purwokerto'],
            ['kode' => 'SLO', 'nama' => 'Solo Balapan', 'kota' => 'Solo'],
            ['kode' => 'ML', 'nama' => 'Malang', 'kota' => 'Malang'],
            ['kode' => 'SBI', 'nama' => 'Surabaya Pasarturi', 'kota' => 'Surabaya'],
            ['kode' => 'LPN', 'nama' => 'Lempuyangan', 'kota' => 'Yogyakarta'],
            ['kode' => 'KAC', 'nama' => 'Kiaracondong', 'kota' => 'Bandung'],
            ['kode' => 'SMC', 'nama' => 'Semarang Poncol', 'kota' => 'Semarang'],
            ['kode' => 'BJR', 'nama' => 'Banjar', 'kota' => 'Banjar'],
            ['kode' => 'TG', 'nama' => 'Tegal', 'kota' => 'Tegal'],
            ['kode' => 'KYA', 'nama' => 'Kroya', 'kota' => 'Cilacap'],
            ['kode' => 'BW', 'nama' => 'Banyuwangi Kota', 'kota' => 'Banyuwangi'],
        ])->mapWithKeys(function (array $baris) {
            $baris['kode'] = strtoupper($baris['kode']);

            return [$baris['kode'] => Stasiun::query()->firstOrCreate(['kode' => $baris['kode']], $baris)];
        });

        $stasiun = Stasiun::query()->get()->keyBy(fn (Stasiun $item) => $item->kode);

        $kereta = collect([
            ['kode' => 'LA', 'nama' => 'Argo Lawu'],
            ['kode' => 'ABA', 'nama' => 'Argo Bromo Anggrek'],
            ['kode' => 'LD', 'nama' => 'Lodaya'],
            ['kode' => 'TR', 'nama' => 'Turangga'],
            ['kode' => 'GJ', 'nama' => 'Gajayana'],
            ['kode' => 'SB', 'nama' => 'Sembrani'],
            ['kode' => 'HR', 'nama' => 'Harina'],
            ['kode' => 'MM', 'nama' => 'Matarmaja'],
        ])->mapWithKeys(fn (array $baris) => [
            $baris['kode'] => Kereta::query()->firstOrCreate(
                ['moda_id' => $moda->id, 'kode' => $baris['kode']],
                ['nama' => $baris['nama']],
            ),
        ]);

        $ekonomi = Kelas::query()->where('nama', 'Ekonomi')->firstOrFail();
        $eksekutif = Kelas::query()->where('nama', 'Eksekutif')->firstOrFail();
        $hari = Carbon::now('Asia/Jakarta')->startOfDay();

        $rute = [
            ['LA', 'GMR', 'SLO', 7, 30, 15, 10, 280000, 520000],
            ['LA', 'SLO', 'GMR', 16, 20, 23, 50, 280000, 520000],
            ['ABA', 'GMR', 'SGU', 8, 15, 16, 40, 450000, 780000],
            ['ABA', 'SGU', 'GMR', 17, 30, 1, 55, 450000, 780000],
            ['LD', 'BD', 'SLO', 6, 40, 16, 5, 250000, 470000],
            ['LD', 'SLO', 'BD', 18, 10, 3, 20, 250000, 470000],
            ['TR', 'BD', 'SGU', 17, 0, 5, 30, 390000, 690000],
            ['TR', 'SGU', 'BD', 15, 40, 4, 10, 390000, 690000],
            ['GJ', 'GMR', 'ML', 18, 30, 6, 15, 420000, 740000],
            ['GJ', 'ML', 'GMR', 14, 15, 2, 5, 420000, 740000],
            ['SB', 'GMR', 'SBI', 19, 10, 4, 40, 400000, 710000],
            ['SB', 'SBI', 'GMR', 18, 0, 3, 25, 400000, 710000],
            ['HR', 'BD', 'SMT', 7, 50, 15, 35, 210000, 390000],
            ['HR', 'SMT', 'BD', 16, 45, 0, 30, 210000, 390000],
            ['MM', 'PSE', 'ML', 9, 20, 23, 45, 190000, 340000],
            ['MM', 'ML', 'PSE', 11, 5, 1, 40, 190000, 340000],
            ['HR', 'CN', 'YK', 8, 5, 14, 20, 230000, 410000],
            ['HR', 'YK', 'CN', 15, 10, 21, 25, 230000, 410000],
        ];

        for ($i = 0; $i < 3; $i++) {
            $tanggal = $hari->copy()->addDays($i);
            foreach ($rute as [$kodeKereta, $asal, $tujuan, $jamBerangkat, $menitBerangkat, $jamTiba, $menitTiba, $hargaEkonomi, $hargaEksekutif]) {
                $berangkat = $tanggal->copy()->setTime($jamBerangkat, $menitBerangkat);
                $tiba = $tanggal->copy()->setTime($jamTiba, $menitTiba);
                if ($tiba->lessThanOrEqualTo($berangkat)) {
                    $tiba->addDay();
                }
                $this->buatJikaBelum(
                    $kereta[$kodeKereta],
                    $stasiun[$asal],
                    $stasiun[$tujuan],
                    $berangkat,
                    $tiba,
                    [$ekonomi->id => $hargaEkonomi, $eksekutif->id => $hargaEksekutif],
                );
            }
        }

        Voucher::query()->firstOrCreate(
            ['kode' => 'KERETA10'],
            ['jenis' => 'persen', 'nilai' => 10, 'kuota' => 100, 'terpakai' => 0, 'aktif' => true],
        );
        Voucher::query()->firstOrCreate(
            ['kode' => 'HEMAT50'],
            ['jenis' => 'potongan', 'nilai' => 50000, 'kuota' => 50, 'terpakai' => 0, 'aktif' => true],
        );
    }

    private function buatJikaBelum(Kereta $kereta, Stasiun $asal, Stasiun $tujuan, Carbon $berangkat, Carbon $tiba, array $harga): void
    {
        $sudah = Jadwal::query()
            ->where('kereta_id', $kereta->id)
            ->where('stasiun_asal_id', $asal->id)
            ->where('stasiun_tujuan_id', $tujuan->id)
            ->where('berangkat_at', $berangkat)
            ->exists();

        if ($sudah) {
            return;
        }

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
            KursiPabrik::isi($jadwal, (int) $kelasId);
        }
    }
}
