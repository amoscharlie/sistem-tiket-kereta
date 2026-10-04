<?php

namespace App\Http\Controllers;

use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;

class AdminPembayaranController extends Controller
{
    public function ringkas(PesananService $service)
    {
        $service->lepaskanKedaluwarsa();

        return response()->json([
            'data' => [
                'menunggu_verifikasi' => Pembayaran::query()->where('status', 'menunggu_verifikasi')->count(),
                'menunggu_bayar' => Pesanan::query()->where('status', 'menunggu_bayar')->count(),
                'lunas' => Pesanan::query()->where('status', 'lunas')->count(),
                'kedaluwarsa' => Pesanan::query()->where('status', 'kedaluwarsa')->count(),
                'ditolak' => Pembayaran::query()->where('status', 'ditolak')->count(),
                'pendapatan' => (float) Pesanan::query()->where('status', 'lunas')->sum('total'),
            ],
        ]);
    }

    public function pesanan(PesananService $service)
    {
        $baris = Pesanan::query()->latest()->limit(50)->get()
            ->map(fn (Pesanan $pesanan) => $service->susun($pesanan));

        return response()->json(['data' => $baris]);
    }

    public function index(PesananService $service)
    {
        $service->lepaskanKedaluwarsa();

        $baris = Pembayaran::query()
            ->where('status', 'menunggu_verifikasi')
            ->latest()
            ->get()
            ->map(fn (Pembayaran $bayar) => $service->susun($bayar->pesanan));

        return response()->json(['data' => $baris]);
    }

    public function setujui(Pembayaran $pembayaran, PesananService $service)
    {
        $this->pastikanMenunggu($pembayaran);
        if (! $pembayaran->bukti_path) {
            abort(422, 'Pelanggan belum mengunggah bukti pembayaran.');
        }

        $pembayaran->status = 'lunas';
        $pembayaran->save();

        $pesanan = $pembayaran->pesanan;
        $pesanan->status = 'lunas';
        $pesanan->save();
        $pesanan->penumpang()->where('status', 'ditahan')->update(['status' => 'terjual']);

        return response()->json(['data' => $service->susun($pesanan->refresh())]);
    }

    public function tolak(Request $request, Pembayaran $pembayaran, PesananService $service)
    {
        $this->pastikanMenunggu($pembayaran);

        $data = $request->validate([
            'catatan' => ['nullable', 'string', 'max:200'],
        ]);

        $pembayaran->status = 'ditolak';
        $pembayaran->catatan = $data['catatan'] ?? 'Bukti ditolak. Unggah ulang.';
        $pembayaran->save();

        $pesanan = $pembayaran->pesanan;
        $pesanan->status = 'menunggu_bayar';
        $pesanan->batas_bayar_at = now()->addHours(2);
        $pesanan->save();

        return response()->json(['data' => $service->susun($pesanan)]);
    }

    public function periksa(Request $request, PesananService $service)
    {
        $data = $request->validate([
            'kode' => ['required', 'string', 'max:80'],
        ]);

        $kode = strtoupper(trim($data['kode']));
        if (preg_match('/kode=([^&\s]+)/i', $kode, $cocok)) {
            $kode = strtoupper(urldecode($cocok[1]));
        }

        $pesanan = Pesanan::query()->where('kode', $kode)->first();
        if (! $pesanan) {
            abort(404, 'Tiket tidak ditemukan.');
        }
        if ($pesanan->status !== 'lunas') {
            abort(422, 'Tiket belum lunas.');
        }
        if ($pesanan->naik_at) {
            abort(422, 'Tiket sudah digunakan pada '.$pesanan->naik_at->timezone('Asia/Jakarta')->format('d M Y H:i').'.');
        }

        $pesanan->naik_at = now();
        $pesanan->save();

        return response()->json(['data' => $service->susun($pesanan)]);
    }

    private function pastikanMenunggu(Pembayaran $pembayaran): void
    {
        if ($pembayaran->status !== 'menunggu_verifikasi') {
            abort(422, 'Pembayaran ini tidak menunggu verifikasi.');
        }
    }
}
