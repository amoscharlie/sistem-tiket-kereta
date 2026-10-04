<?php

namespace App\Services;

use App\Models\Jadwal;
use App\Models\Kursi;

class KursiPabrik
{
    public static function isi(Jadwal $jadwal, int $kelasId): void
    {
        foreach (range(1, 4) as $baris) {
            foreach (['A', 'B', 'C', 'D'] as $kolom) {
                $kursi = new Kursi;
                $kursi->jadwal_id = $jadwal->id;
                $kursi->kelas_id = $kelasId;
                $kursi->kode = $baris.$kolom;
                $kursi->baris = $baris;
                $kursi->kolom = $kolom;
                $kursi->save();
            }
        }
    }
}
