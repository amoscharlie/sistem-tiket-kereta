<?php

namespace App\Services;

use App\Models\JadwalKelas;
use App\Models\Kursi;
use App\Models\Pembayaran;
use App\Models\Penumpang;
use App\Models\Pesanan;
use App\Models\User;
use App\Models\Voucher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PesananService
{
    public function lepaskanKedaluwarsa(): void
    {
        $ids = Pesanan::query()
            ->where('status', 'menunggu_bayar')
            ->where('batas_bayar_at', '<', now())
            ->pluck('id');

        if ($ids->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($ids) {
            Penumpang::query()
                ->whereIn('pesanan_id', $ids)
                ->where('status', 'ditahan')
                ->update(['status' => 'dilepas']);

            Pesanan::query()
                ->whereIn('id', $ids)
                ->update(['status' => 'kedaluwarsa']);
        });
    }

    public function buat(
        User $user,
        int $jadwalId,
        int $kelasId,
        array $penumpang,
        ?int $jadwalPulangId = null,
        ?int $kelasPulangId = null,
        ?string $voucherKode = null,
        array $bayi = [],
    ): Pesanan {
        if (! $user->sudahVerifikasi()) {
            abort(403, 'Verifikasi email, nomor HP, dan WhatsApp terlebih dahulu.');
        }

        if (count($bayi) > count($penumpang)) {
            abort(422, 'Satu bayi gratis harus ikut satu penumpang yang membeli tiket.');
        }

        $this->lepaskanKedaluwarsa();

        $harga = JadwalKelas::query()
            ->where('jadwal_id', $jadwalId)
            ->where('kelas_id', $kelasId)
            ->firstOrFail();

        $hargaPulang = null;
        if ($jadwalPulangId && $kelasPulangId) {
            $hargaPulang = JadwalKelas::query()
                ->where('jadwal_id', $jadwalPulangId)
                ->where('kelas_id', $kelasPulangId)
                ->firstOrFail();
        }

        $kursiPergi = collect($penumpang)->pluck('kursi_id')->map(fn ($id) => (int) $id)->all();
        $kursiPulang = $hargaPulang
            ? collect($penumpang)->pluck('kursi_pulang_id')->map(fn ($id) => (int) $id)->all()
            : [];

        try {
            return DB::transaction(function () use ($user, $jadwalId, $kelasId, $penumpang, $bayi, $harga, $hargaPulang, $jadwalPulangId, $kelasPulangId, $kursiPergi, $kursiPulang, $voucherKode) {
                $this->kunciKursi($kursiPergi, $jadwalId, $kelasId);
                if ($hargaPulang) {
                    $this->kunciKursi($kursiPulang, $jadwalPulangId, $kelasPulangId);
                }

                $semuaKursi = array_merge($kursiPergi, $kursiPulang);
                $bentrok = Penumpang::query()
                    ->whereIn('kursi_id', $semuaKursi)
                    ->whereIn('status', ['ditahan', 'terjual'])
                    ->exists();

                if ($bentrok) {
                    abort(409, 'Salah satu kursi baru saja dipesan.');
                }

                $subtotal = (float) $harga->harga * count($penumpang);
                if ($hargaPulang) {
                    $subtotal += (float) $hargaPulang->harga * count($penumpang);
                }

                $voucher = null;
                $potongan = 0;
                if ($voucherKode) {
                    $voucher = Voucher::query()->where('kode', strtoupper($voucherKode))->lockForUpdate()->first();
                    if (! $voucher || ! $voucher->aktif || $voucher->terpakai >= $voucher->kuota) {
                        abort(422, 'Voucher tidak berlaku.');
                    }
                    if ($voucher->berlaku_sampai && $voucher->berlaku_sampai->endOfDay()->lt(now())) {
                        abort(422, 'Voucher sudah kedaluwarsa.');
                    }
                    $potongan = $voucher->jenis === 'persen'
                        ? $subtotal * ((float) $voucher->nilai / 100)
                        : min($subtotal, (float) $voucher->nilai);
                    $voucher->terpakai++;
                    $voucher->save();
                }

                $pesanan = new Pesanan;
                $pesanan->kode = $this->kodeBaru();
                $pesanan->user_id = $user->id;
                $pesanan->jadwal_id = $jadwalId;
                $pesanan->kelas_id = $kelasId;
                $pesanan->jadwal_pulang_id = $jadwalPulangId;
                $pesanan->kelas_pulang_id = $kelasPulangId;
                $pesanan->voucher_id = $voucher?->id;
                $pesanan->potongan = $potongan;
                $pesanan->status = 'menunggu_bayar';
                $pesanan->total = max(0, $subtotal - $potongan);
                $pesanan->batas_bayar_at = now()->addHours(2);
                $pesanan->save();

                foreach ($penumpang as $orang) {
                    $this->simpanPenumpang($pesanan->id, (int) $orang['kursi_id'], $orang['nama'], (int) $orang['umur'], $orang['nik']);
                    if ($hargaPulang) {
                        $this->simpanPenumpang($pesanan->id, (int) $orang['kursi_pulang_id'], $orang['nama'], (int) $orang['umur'], $orang['nik']);
                    }
                }

                foreach ($bayi as $anak) {
                    $this->simpanPenumpang($pesanan->id, null, $anak['nama'], (int) $anak['umur'], $anak['nik'] ?? null);
                }

                $bayar = new Pembayaran;
                $bayar->pesanan_id = $pesanan->id;
                $bayar->metode = 'manual';
                $bayar->status = 'menunggu_bukti';
                $bayar->jumlah = $pesanan->total;
                $bayar->save();

                return $pesanan->load(['jadwal.asal', 'jadwal.tujuan', 'jadwal.kereta', 'kelas', 'jadwalPulang.asal', 'jadwalPulang.tujuan', 'jadwalPulang.kereta', 'kelasPulang', 'penumpang.kursi', 'pembayaran', 'voucher']);
            });
        } catch (UniqueConstraintViolationException) {
            abort(409, 'Salah satu kursi baru saja dipesan.');
        }
    }

    private function kunciKursi(array $ids, int $jadwalId, int $kelasId): void
    {
        $kursi = Kursi::query()
            ->whereIn('id', $ids)
            ->where('jadwal_id', $jadwalId)
            ->where('kelas_id', $kelasId)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($kursi->count() !== count($ids)) {
            abort(422, 'Kursi tidak sesuai dengan jadwal dan kelas yang dipilih.');
        }
    }

    public function batal(Pesanan $pesanan): Pesanan
    {
        if (! in_array($pesanan->status, ['menunggu_bayar', 'menunggu_verifikasi'], true)) {
            abort(422, 'Pesanan yang sudah lunas tidak bisa dibatalkan.');
        }

        return DB::transaction(function () use ($pesanan) {
            $pesanan->penumpang()->where('status', 'ditahan')->update(['status' => 'dilepas']);
            if ($pesanan->voucher_id) {
                Voucher::query()->whereKey($pesanan->voucher_id)->where('terpakai', '>', 0)->decrement('terpakai');
            }
            $pesanan->status = 'batal';
            $pesanan->save();

            return $pesanan->refresh();
        });
    }

    private function simpanPenumpang(int $pesananId, ?int $kursiId, string $nama, int $umur, ?string $nik): void
    {
        $baris = new Penumpang;
        $baris->pesanan_id = $pesananId;
        $baris->kursi_id = $kursiId;
        $baris->nama = $nama;
        $baris->umur = $umur;
        $baris->nik = $nik;
        $baris->status = 'ditahan';
        $baris->save();
    }

    public function susun(Pesanan $pesanan): array
    {
        $pesanan->loadMissing(['jadwal.asal', 'jadwal.tujuan', 'jadwal.kereta', 'kelas', 'jadwalPulang.asal', 'jadwalPulang.tujuan', 'jadwalPulang.kereta', 'kelasPulang', 'penumpang.kursi', 'pembayaran', 'user', 'voucher']);

        return [
            'id' => $pesanan->id,
            'kode' => $pesanan->kode,
            'status' => $pesanan->status,
            'total' => $pesanan->total,
            'potongan' => $pesanan->potongan,
            'voucher' => $pesanan->voucher?->kode,
            'batas_bayar_at' => $pesanan->batas_bayar_at,
            'naik_at' => $pesanan->naik_at,
            'pemesan' => $pesanan->user?->toProfil(),
            'kereta' => $pesanan->jadwal->kereta->nama,
            'asal' => $pesanan->jadwal->asal->nama,
            'tujuan' => $pesanan->jadwal->tujuan->nama,
            'berangkat_at' => $pesanan->jadwal->berangkat_at,
            'kelas' => $pesanan->kelas->nama,
            'pulang' => $pesanan->jadwalPulang ? [
                'kereta' => $pesanan->jadwalPulang->kereta->nama,
                'asal' => $pesanan->jadwalPulang->asal->nama,
                'tujuan' => $pesanan->jadwalPulang->tujuan->nama,
                'berangkat_at' => $pesanan->jadwalPulang->berangkat_at,
                'kelas' => $pesanan->kelasPulang?->nama,
            ] : null,
            'penumpang' => $pesanan->penumpang->map(fn (Penumpang $orang) => [
                'nama' => $orang->nama,
                'nik' => $orang->nik,
                'umur' => $orang->umur,
                'kursi' => $orang->kursi?->kode,
                'arah' => $orang->kursi_id === null
                    ? 'bayi'
                    : ($orang->kursi->jadwal_id === $pesanan->jadwal_id ? 'pergi' : 'pulang'),
                'gratis' => $orang->kursi_id === null,
                'status' => $orang->status,
            ])->values(),
            'pembayaran' => $pesanan->pembayaran ? [
                'id' => $pesanan->pembayaran->id,
                'metode' => $pesanan->pembayaran->metode,
                'status' => $pesanan->pembayaran->status,
                'jumlah' => $pesanan->pembayaran->jumlah,
                'bukti_url' => $pesanan->pembayaran->bukti_path
                    ? asset('storage/'.$pesanan->pembayaran->bukti_path)
                    : null,
                'catatan' => $pesanan->pembayaran->catatan,
            ] : null,
        ];
    }

    private function kodeBaru(): string
    {
        do {
            $kode = 'KA'.strtoupper(Str::random(6));
        } while (Pesanan::query()->where('kode', $kode)->exists());

        return $kode;
    }
}
