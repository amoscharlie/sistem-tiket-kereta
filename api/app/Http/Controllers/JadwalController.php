<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\Kursi;
use App\Models\Penumpang;
use App\Models\Stasiun;
use App\Services\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class JadwalController extends Controller
{
    public function stasiun()
    {
        $stasiun = Stasiun::query()->orderBy('kota')->orderBy('nama')->get(['id', 'kode', 'nama', 'kota']);

        return response()->json(['data' => $stasiun]);
    }

    public function index(Request $request, PesananService $pesanan)
    {
        $pesanan->lepaskanKedaluwarsa();

        $data = $request->validate([
            'asal' => ['required', 'integer', 'exists:stasiun,id'],
            'tujuan' => ['required', 'integer', 'exists:stasiun,id', 'different:asal'],
            'tanggal' => ['required', 'date_format:Y-m-d'],
            'pulang' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:tanggal'],
        ]);

        $pergi = $this->cariPada($data['asal'], $data['tujuan'], $data['tanggal']);
        $pulang = empty($data['pulang'])
            ? null
            : $this->cariPada($data['tujuan'], $data['asal'], $data['pulang']);

        return response()->json([
            'data' => $pergi,
            'pulang' => $pulang,
            'rekomendasi' => $pergi->isEmpty()
                ? $this->rekomendasi((int) $data['asal'], (int) $data['tujuan'], $data['tanggal'])
                : [],
            'rekomendasi_pulang' => $pulang === null
                ? null
                : ($pulang->isEmpty()
                    ? $this->rekomendasi((int) $data['tujuan'], (int) $data['asal'], $data['pulang'])
                    : []),
        ]);
    }

    private function rekomendasi(int $asalId, int $tujuanId, string $tanggal)
    {
        $asal = Stasiun::query()->findOrFail($asalId);
        $tujuan = Stasiun::query()->findOrFail($tujuanId);
        $pusat = Carbon::parse($tanggal, 'Asia/Jakarta')->startOfDay();
        $dari = $pusat->copy()->subDays(3);
        $sampai = $pusat->copy()->addDays(4);
        $idAsal = Stasiun::query()->where('kota', $asal->kota)->pluck('id');
        $idTujuan = Stasiun::query()->where('kota', $tujuan->kota)->pluck('id');

        $jadwal = $this->ambilRentang($dari, $sampai, function ($query) use ($idAsal, $idTujuan) {
            $query->whereIn('stasiun_asal_id', $idAsal)->whereIn('stasiun_tujuan_id', $idTujuan);
        });

        $jadwal = $jadwal->reject(function (Jadwal $item) use ($asalId, $tujuanId, $pusat) {
            return $item->stasiun_asal_id === $asalId
                && $item->stasiun_tujuan_id === $tujuanId
                && $item->berangkat_at->timezone('Asia/Jakarta')->isSameDay($pusat);
        })->values();

        $semuaStasiun = false;
        if ($jadwal->isEmpty()) {
            $semuaStasiun = true;
            $jadwal = $this->ambilRentang($dari, $sampai, function ($query) use ($idAsal, $idTujuan) {
                $query->where(function ($saring) use ($idAsal, $idTujuan) {
                    $saring->whereIn('stasiun_asal_id', $idAsal)->orWhereIn('stasiun_tujuan_id', $idTujuan);
                });
            });
        }

        $terpakai = $this->hitungTerpakai($jadwal->pluck('id'));

        return $jadwal
            ->sortBy(fn (Jadwal $item) => abs($item->berangkat_at->getTimestamp() - $pusat->getTimestamp()))
            ->take(8)
            ->map(function (Jadwal $item) use ($terpakai, $asal, $tujuan, $asalId, $tujuanId, $tanggal, $semuaStasiun) {
                $ringkas = $this->ringkas($item, $terpakai);
                $bedaStasiun = $item->stasiun_asal_id !== $asalId || $item->stasiun_tujuan_id !== $tujuanId;
                $bedaTanggal = $item->berangkat_at->timezone('Asia/Jakarta')->toDateString() !== $tanggal;
                $bedaKota = $item->asal->kota !== $asal->kota || $item->tujuan->kota !== $tujuan->kota;
                $ringkas['alasan'] = $semuaStasiun || $bedaKota
                    ? 'Rute lain'
                    : ($bedaStasiun && $bedaTanggal ? 'Stasiun dan tanggal lain' : ($bedaStasiun ? 'Stasiun lain' : 'Tanggal lain'));

                return $ringkas;
            })
            ->values();
    }

    private function ambilRentang(Carbon $dari, Carbon $sampai, callable $saring)
    {
        return Jadwal::query()
            ->with(['kereta.moda', 'asal', 'tujuan', 'hargaKelas.kelas'])
            ->where('berangkat_at', '>=', $dari)
            ->where('berangkat_at', '<', $sampai)
            ->where($saring)
            ->orderBy('berangkat_at')
            ->limit(40)
            ->get();
    }

    private function cariPada($asal, $tujuan, string $tanggal)
    {
        $mulai = Carbon::parse($tanggal, 'Asia/Jakarta')->startOfDay();
        $selesai = $mulai->copy()->addDay();

        $jadwal = Jadwal::query()
            ->with(['kereta.moda', 'asal', 'tujuan', 'hargaKelas.kelas'])
            ->where('stasiun_asal_id', $asal)
            ->where('stasiun_tujuan_id', $tujuan)
            ->where('berangkat_at', '>=', $mulai)
            ->where('berangkat_at', '<', $selesai)
            ->orderBy('berangkat_at')
            ->get();

        $terpakai = $this->hitungTerpakai($jadwal->pluck('id'));

        return $jadwal->map(fn (Jadwal $item) => $this->ringkas($item, $terpakai))->values();
    }

    public function show(Jadwal $jadwal, PesananService $pesanan)
    {
        $pesanan->lepaskanKedaluwarsa();

        $jadwal->load(['kereta.moda', 'asal', 'tujuan', 'hargaKelas.kelas']);

        $kursi = Kursi::query()
            ->where('jadwal_id', $jadwal->id)
            ->orderBy('kelas_id')
            ->orderBy('baris')
            ->orderBy('kolom')
            ->get();

        $aktif = Penumpang::query()
            ->whereIn('kursi_id', $kursi->pluck('id'))
            ->whereIn('status', ['ditahan', 'terjual'])
            ->pluck('kursi_id')
            ->flip();

        $kelas = $jadwal->hargaKelas
            ->sortBy(fn ($harga) => $harga->kelas->urutan)
            ->map(function ($harga) use ($kursi, $aktif) {
                return [
                    'id' => $harga->kelas_id,
                    'nama' => $harga->kelas->nama,
                    'harga' => $harga->harga,
                    'kursi' => $kursi
                        ->where('kelas_id', $harga->kelas_id)
                        ->map(fn (Kursi $seat) => [
                            'id' => $seat->id,
                            'kode' => $seat->kode,
                            'baris' => $seat->baris,
                            'kolom' => $seat->kolom,
                            'tersedia' => ! $aktif->has($seat->id),
                        ])->values(),
                ];
            })->values();

        return response()->json([
            'data' => [
                'id' => $jadwal->id,
                'kereta' => $jadwal->kereta->nama,
                'moda' => $jadwal->kereta->moda->kode,
                'asal' => $jadwal->asal->nama,
                'tujuan' => $jadwal->tujuan->nama,
                'berangkat_at' => $jadwal->berangkat_at,
                'tiba_at' => $jadwal->tiba_at,
                'kelas' => $kelas,
            ],
        ]);
    }

    private function hitungTerpakai($jadwalIds)
    {
        if ($jadwalIds->isEmpty()) {
            return collect();
        }

        return Penumpang::query()
            ->join('kursi', 'kursi.id', '=', 'penumpang.kursi_id')
            ->whereIn('penumpang.status', ['ditahan', 'terjual'])
            ->whereIn('kursi.jadwal_id', $jadwalIds)
            ->selectRaw('kursi.jadwal_id, kursi.kelas_id, count(*) as jumlah')
            ->groupBy('kursi.jadwal_id', 'kursi.kelas_id')
            ->get()
            ->keyBy(fn ($baris) => $baris->jadwal_id.'-'.$baris->kelas_id);
    }

    private function ringkas(Jadwal $jadwal, $terpakai): array
    {
        return [
            'id' => $jadwal->id,
            'kereta' => $jadwal->kereta->nama,
            'moda' => $jadwal->kereta->moda->kode,
            'asal' => $jadwal->asal->nama,
            'tujuan' => $jadwal->tujuan->nama,
            'berangkat_at' => $jadwal->berangkat_at,
            'tiba_at' => $jadwal->tiba_at,
            'kelas' => $jadwal->hargaKelas
                ->sortBy(fn ($harga) => $harga->kelas->urutan)
                ->map(function ($harga) use ($jadwal, $terpakai) {
                    $jumlah = Kursi::query()
                        ->where('jadwal_id', $jadwal->id)
                        ->where('kelas_id', $harga->kelas_id)
                        ->count();
                    $pakai = (int) ($terpakai->get($jadwal->id.'-'.$harga->kelas_id)->jumlah ?? 0);

                    return [
                        'id' => $harga->kelas_id,
                        'nama' => $harga->kelas->nama,
                        'harga' => $harga->harga,
                        'sisa' => $jumlah - $pakai,
                    ];
                })->values(),
        ];
    }
}
