<?php

namespace App\Http\Controllers;

use App\Models\Jadwal;
use App\Models\JadwalKelas;
use App\Models\Kelas;
use App\Models\Kereta;
use App\Models\Moda;
use App\Models\Kursi;
use App\Models\Pembayaran;
use App\Models\Penumpang;
use App\Models\Pesanan;
use App\Models\Stasiun;
use App\Models\Voucher;
use App\Services\KursiPabrik;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class PengelolaController extends Controller
{
    public function master()
    {
        return response()->json([
            'stasiun' => Stasiun::query()->orderBy('kota')->orderBy('nama')->get(),
            'kereta' => Kereta::query()->orderBy('nama')->get(),
            'kelas' => Kelas::query()->orderBy('urutan')->get(),
        ]);
    }

    public function ringkas()
    {
        $hariIni = now()->toDateString();

        return response()->json([
            'data' => [
                'stasiun' => Stasiun::query()->count(),
                'jadwal' => Jadwal::query()->count(),
                'jadwal_hari_ini' => Jadwal::query()->whereDate('berangkat_at', $hariIni)->count(),
                'voucher_aktif' => Voucher::query()->where('aktif', true)->count(),
                'kursi' => Kursi::query()->count(),
                'terjual' => Penumpang::query()->where('status', 'terjual')->whereNotNull('kursi_id')->count(),
                'pendapatan' => (float) Pesanan::query()->where('status', 'lunas')->sum('total'),
                'pesanan_lunas' => Pesanan::query()->where('status', 'lunas')->count(),
                'pesanan_menunggu' => Pesanan::query()->whereIn('status', ['menunggu_bayar', 'menunggu_verifikasi'])->count(),
            ],
        ]);
    }

    public function laporanPerjalanan(Request $request)
    {
        $tanggal = $request->query('tanggal');
        $jadwal = Jadwal::query()
            ->with(['kereta', 'asal', 'tujuan', 'hargaKelas.kelas'])
            ->when($tanggal, fn ($query) => $query->whereDate('berangkat_at', $tanggal))
            ->orderBy('berangkat_at')
            ->get();

        $ids = $jadwal->pluck('id');
        $pakai = Penumpang::query()
            ->join('kursi', 'kursi.id', '=', 'penumpang.kursi_id')
            ->whereIn('penumpang.status', ['terjual', 'ditahan'])
            ->whereIn('kursi.jadwal_id', $ids)
            ->selectRaw('kursi.jadwal_id, kursi.kelas_id, penumpang.status, count(*) as jumlah')
            ->groupBy('kursi.jadwal_id', 'kursi.kelas_id', 'penumpang.status')
            ->get();
        $kapasitas = Kursi::query()
            ->whereIn('jadwal_id', $ids)
            ->selectRaw('jadwal_id, kelas_id, count(*) as jumlah')
            ->groupBy('jadwal_id', 'kelas_id')
            ->get();

        $baris = [];
        foreach ($jadwal as $item) {
            foreach ($item->hargaKelas->sortBy(fn ($harga) => $harga->kelas->urutan) as $harga) {
                $ambil = fn (string $status) => (int) ($pakai->first(
                    fn ($row) => (int) $row->jadwal_id === $item->id
                        && (int) $row->kelas_id === $harga->kelas_id
                        && $row->status === $status
                )->jumlah ?? 0);
                $kursi = (int) ($kapasitas->first(
                    fn ($row) => (int) $row->jadwal_id === $item->id && (int) $row->kelas_id === $harga->kelas_id
                )->jumlah ?? 0);
                $terjual = $ambil('terjual');
                $ditahan = $ambil('ditahan');
                $baris[] = [
                    'id' => $item->id.'-'.$harga->kelas_id,
                    'kereta' => $item->kereta->nama,
                    'asal' => $item->asal->nama,
                    'tujuan' => $item->tujuan->nama,
                    'berangkat_at' => $item->berangkat_at,
                    'kelas' => $harga->kelas->nama,
                    'harga' => $harga->harga,
                    'kapasitas' => $kursi,
                    'terjual' => $terjual,
                    'ditahan' => $ditahan,
                    'sisa' => max(0, $kursi - $terjual - $ditahan),
                    'nilai_terjual' => $terjual * (float) $harga->harga,
                ];
            }
        }

        return response()->json(['data' => $baris]);
    }

    public function laporanPembayaran(Request $request)
    {
        $status = $request->query('status');
        $baris = Pembayaran::query()
            ->with(['pesanan.user', 'pesanan.jadwal.kereta', 'pesanan.jadwal.asal', 'pesanan.jadwal.tujuan'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->limit(300)
            ->get()
            ->map(function (Pembayaran $bayar) {
                $pesanan = $bayar->pesanan;

                return [
                    'id' => $bayar->id,
                    'kode' => $pesanan?->kode,
                    'pemesan' => $pesanan?->user?->name,
                    'rute' => $pesanan ? $pesanan->jadwal->asal->nama.' ke '.$pesanan->jadwal->tujuan->nama : null,
                    'kereta' => $pesanan?->jadwal?->kereta?->nama,
                    'metode' => $bayar->metode,
                    'status' => $bayar->status,
                    'jumlah' => $bayar->jumlah,
                    'dibuat_at' => $bayar->created_at,
                ];
            });

        $ringkas = Pembayaran::query()
            ->selectRaw('status, count(*) as banyak, coalesce(sum(pembayaran.jumlah), 0) as total')
            ->groupBy('status')
            ->get();

        return response()->json(['data' => $baris, 'ringkas' => $ringkas]);
    }

    public function simpanStasiun(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:8', 'unique:stasiun,kode'],
            'nama' => ['required', 'string', 'max:80'],
            'kota' => ['required', 'string', 'max:80'],
        ]);

        $stasiun = new Stasiun;
        $stasiun->fill($data);
        $stasiun->kode = strtoupper($data['kode']);
        $stasiun->save();

        return response()->json(['data' => $stasiun], 201);
    }

    public function ubahStasiun(Request $request, Stasiun $stasiun)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:8', Rule::unique('stasiun', 'kode')->ignore($stasiun->id)],
            'nama' => ['required', 'string', 'max:80'],
            'kota' => ['required', 'string', 'max:80'],
        ]);

        $stasiun->kode = strtoupper($data['kode']);
        $stasiun->nama = $data['nama'];
        $stasiun->kota = $data['kota'];
        $stasiun->save();

        return response()->json(['data' => $stasiun]);
    }

    public function hapusStasiun(Stasiun $stasiun)
    {
        $dipakai = Jadwal::query()
            ->where('stasiun_asal_id', $stasiun->id)
            ->orWhere('stasiun_tujuan_id', $stasiun->id)
            ->exists();

        if ($dipakai) {
            abort(422, 'Stasiun ini masih dipakai jadwal.');
        }

        $stasiun->delete();

        return response()->json(['message' => 'Stasiun dihapus.']);
    }

    public function simpanKereta(Request $request)
    {
        $data = $this->validasiKereta($request);
        $kereta = new Kereta;
        $kereta->moda_id = $this->modaKereta();
        $kereta->kode = strtoupper($data['kode']);
        $kereta->nama = $data['nama'];
        $kereta->save();

        return response()->json(['data' => $kereta], 201);
    }

    public function ubahKereta(Request $request, Kereta $kereta)
    {
        $data = $this->validasiKereta($request, $kereta->id);
        $kereta->kode = strtoupper($data['kode']);
        $kereta->nama = $data['nama'];
        $kereta->save();

        return response()->json(['data' => $kereta]);
    }

    public function hapusKereta(Kereta $kereta)
    {
        if (Jadwal::query()->where('kereta_id', $kereta->id)->exists()) {
            abort(422, 'Kereta ini masih dipakai jadwal.');
        }

        $kereta->delete();

        return response()->json(['message' => 'Kereta dihapus.']);
    }

    public function jadwal()
    {
        $jadwal = Jadwal::query()
            ->with(['kereta', 'asal', 'tujuan', 'hargaKelas.kelas'])
            ->orderByDesc('berangkat_at')
            ->get()
            ->map(fn (Jadwal $item) => $this->susunJadwal($item));

        return response()->json(['data' => $jadwal]);
    }

    public function simpanJadwal(Request $request)
    {
        $data = $request->validate([
            'kereta_id' => ['required', 'integer', 'exists:kereta,id'],
            'stasiun_asal_id' => ['required', 'integer', 'exists:stasiun,id'],
            'stasiun_tujuan_id' => ['required', 'integer', 'exists:stasiun,id', 'different:stasiun_asal_id'],
            'berangkat_at' => ['required', 'date'],
            'tiba_at' => ['required', 'date', 'after:berangkat_at'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'kelas.*.harga' => ['required', 'numeric', 'min:1'],
        ]);

        $jadwal = new Jadwal;
        $jadwal->kereta_id = $data['kereta_id'];
        $jadwal->stasiun_asal_id = $data['stasiun_asal_id'];
        $jadwal->stasiun_tujuan_id = $data['stasiun_tujuan_id'];
        $jadwal->berangkat_at = Carbon::parse($data['berangkat_at'], 'Asia/Jakarta');
        $jadwal->tiba_at = Carbon::parse($data['tiba_at'], 'Asia/Jakarta');
        $jadwal->save();

        foreach ($data['kelas'] as $baris) {
            $harga = new JadwalKelas;
            $harga->jadwal_id = $jadwal->id;
            $harga->kelas_id = $baris['kelas_id'];
            $harga->harga = $baris['harga'];
            $harga->save();
            KursiPabrik::isi($jadwal, (int) $baris['kelas_id']);
        }

        return response()->json(['data' => ['id' => $jadwal->id]], 201);
    }

    public function ubahJadwal(Request $request, Jadwal $jadwal)
    {
        $data = $this->validasiJadwal($request);

        $jadwal->kereta_id = $data['kereta_id'];
        $jadwal->stasiun_asal_id = $data['stasiun_asal_id'];
        $jadwal->stasiun_tujuan_id = $data['stasiun_tujuan_id'];
        $jadwal->berangkat_at = Carbon::parse($data['berangkat_at'], 'Asia/Jakarta');
        $jadwal->tiba_at = Carbon::parse($data['tiba_at'], 'Asia/Jakarta');
        $jadwal->save();

        foreach ($data['kelas'] as $baris) {
            $harga = JadwalKelas::query()->firstOrNew([
                'jadwal_id' => $jadwal->id,
                'kelas_id' => $baris['kelas_id'],
            ]);
            $baru = ! $harga->exists;
            $harga->harga = $baris['harga'];
            $harga->save();
            if ($baru) {
                KursiPabrik::isi($jadwal, (int) $baris['kelas_id']);
            }
        }

        return response()->json(['data' => $this->susunJadwal($jadwal->fresh(['kereta', 'asal', 'tujuan', 'hargaKelas.kelas']))]);
    }

    public function hapusJadwal(Jadwal $jadwal)
    {
        $dipakai = Pesanan::query()
            ->where('jadwal_id', $jadwal->id)
            ->orWhere('jadwal_pulang_id', $jadwal->id)
            ->exists();

        if ($dipakai) {
            abort(422, 'Jadwal ini sudah dipakai pesanan.');
        }

        $jadwal->delete();

        return response()->json(['message' => 'Jadwal dihapus.']);
    }

    public function voucher()
    {
        return response()->json(['data' => Voucher::query()->latest()->get()]);
    }

    public function simpanVoucher(Request $request)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', 'unique:voucher,kode'],
            'jenis' => ['required', 'in:potongan,persen'],
            'nilai' => ['required', 'numeric', 'min:1'],
            'kuota' => ['required', 'integer', 'min:1'],
            'berlaku_sampai' => ['nullable', 'date'],
        ]);

        $voucher = new Voucher;
        $voucher->kode = strtoupper($data['kode']);
        $voucher->jenis = $data['jenis'];
        $voucher->nilai = $data['nilai'];
        $voucher->kuota = $data['kuota'];
        $voucher->terpakai = 0;
        $voucher->berlaku_sampai = $data['berlaku_sampai'] ?? null;
        $voucher->aktif = true;
        $voucher->save();

        return response()->json(['data' => $voucher], 201);
    }

    public function ubahVoucher(Request $request, Voucher $voucher)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:30', Rule::unique('voucher', 'kode')->ignore($voucher->id)],
            'jenis' => ['required', 'in:potongan,persen'],
            'nilai' => ['required', 'numeric', 'min:1'],
            'kuota' => ['required', 'integer', 'min:1'],
            'berlaku_sampai' => ['nullable', 'date'],
            'aktif' => ['required', 'boolean'],
        ]);

        if ((int) $data['kuota'] < $voucher->terpakai) {
            abort(422, 'Kuota tidak boleh lebih kecil dari yang sudah terpakai.');
        }

        $voucher->kode = strtoupper($data['kode']);
        $voucher->jenis = $data['jenis'];
        $voucher->nilai = $data['nilai'];
        $voucher->kuota = $data['kuota'];
        $voucher->berlaku_sampai = $data['berlaku_sampai'] ?? null;
        $voucher->aktif = $data['aktif'];
        $voucher->save();

        return response()->json(['data' => $voucher]);
    }

    public function hapusVoucher(Voucher $voucher)
    {
        if (Pesanan::query()->where('voucher_id', $voucher->id)->exists()) {
            abort(422, 'Voucher ini sudah dipakai pesanan.');
        }

        $voucher->delete();

        return response()->json(['message' => 'Voucher dihapus.']);
    }

    private function modaKereta(): int
    {
        return (int) Moda::query()->where('kode', 'kereta')->value('id');
    }

    private function validasiKereta(Request $request, ?int $abaikan = null): array
    {
        return $request->validate([
            'kode' => ['required', 'string', 'max:8', Rule::unique('kereta', 'kode')->ignore($abaikan)],
            'nama' => ['required', 'string', 'max:80'],
        ]);
    }

    private function validasiJadwal(Request $request): array
    {
        return $request->validate([
            'kereta_id' => ['required', 'integer', 'exists:kereta,id'],
            'stasiun_asal_id' => ['required', 'integer', 'exists:stasiun,id'],
            'stasiun_tujuan_id' => ['required', 'integer', 'exists:stasiun,id', 'different:stasiun_asal_id'],
            'berangkat_at' => ['required', 'date'],
            'tiba_at' => ['required', 'date', 'after:berangkat_at'],
            'kelas' => ['required', 'array', 'min:1'],
            'kelas.*.kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'kelas.*.harga' => ['required', 'numeric', 'min:1'],
        ]);
    }

    private function susunJadwal(Jadwal $item): array
    {
        return [
            'id' => $item->id,
            'kereta_id' => $item->kereta_id,
            'kereta' => $item->kereta->nama,
            'stasiun_asal_id' => $item->stasiun_asal_id,
            'stasiun_tujuan_id' => $item->stasiun_tujuan_id,
            'asal' => $item->asal->nama,
            'tujuan' => $item->tujuan->nama,
            'berangkat_at' => $item->berangkat_at,
            'tiba_at' => $item->tiba_at,
            'kelas' => $item->hargaKelas
                ->sortBy(fn ($harga) => $harga->kelas->urutan)
                ->map(fn ($harga) => [
                    'kelas_id' => $harga->kelas_id,
                    'nama' => $harga->kelas->nama,
                    'harga' => $harga->harga,
                ])->values(),
        ];
    }
}
