<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Services\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PesananController extends Controller
{
    public function tiket(string $kode, PesananService $service)
    {
        $pesanan = Pesanan::query()->where('kode', strtoupper($kode))->first();
        if (! $pesanan || $pesanan->status !== 'lunas') {
            abort(404, 'Tiket tidak ditemukan atau belum lunas.');
        }

        return response()->json(['data' => $service->susun($pesanan)]);
    }

    public function index(Request $request, PesananService $service)
    {
        $service->lepaskanKedaluwarsa();

        $pesanan = Pesanan::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Pesanan $item) => $service->susun($item));

        return response()->json(['data' => $pesanan]);
    }

    public function show(Request $request, Pesanan $pesanan, PesananService $service)
    {
        $this->pastikanMilik($request, $pesanan);
        $service->lepaskanKedaluwarsa();
        $pesanan->refresh();

        return response()->json(['data' => $service->susun($pesanan)]);
    }

    public function simpan(Request $request, PesananService $service)
    {
        $data = $request->validate([
            'jadwal_id' => ['required', 'integer', 'exists:jadwal,id'],
            'kelas_id' => ['required', 'integer', 'exists:kelas,id'],
            'penumpang' => ['required', 'array', 'min:1', 'max:4'],
            'penumpang.*.kursi_id' => ['required', 'integer', 'distinct', 'exists:kursi,id'],
            'penumpang.*.nama' => ['required', 'string', 'max:80'],
            'penumpang.*.umur' => ['required', 'integer', 'min:3', 'max:120'],
            'penumpang.*.nik' => ['required', 'digits:16'],
            'bayi' => ['nullable', 'array', 'max:4'],
            'bayi.*.nama' => ['required', 'string', 'max:80'],
            'bayi.*.umur' => ['required', 'integer', 'min:0', 'max:2'],
            'jadwal_pulang_id' => ['nullable', 'integer', 'exists:jadwal,id'],
            'kelas_pulang_id' => ['required_with:jadwal_pulang_id', 'integer', 'exists:kelas,id'],
            'penumpang.*.kursi_pulang_id' => ['required_with:jadwal_pulang_id', 'integer', 'distinct', 'exists:kursi,id'],
            'voucher' => ['nullable', 'string', 'max:30'],
        ]);

        $pesanan = $service->buat(
            $request->user(),
            (int) $data['jadwal_id'],
            (int) $data['kelas_id'],
            $data['penumpang'],
            isset($data['jadwal_pulang_id']) ? (int) $data['jadwal_pulang_id'] : null,
            isset($data['kelas_pulang_id']) ? (int) $data['kelas_pulang_id'] : null,
            $data['voucher'] ?? null,
            $data['bayi'] ?? [],
        );

        return response()->json(['data' => $service->susun($pesanan)], 201);
    }

    public function batal(Request $request, Pesanan $pesanan, PesananService $service)
    {
        $this->pastikanMilik($request, $pesanan);

        return response()->json(['data' => $service->susun($service->batal($pesanan))]);
    }

    public function bukti(Request $request, Pesanan $pesanan, PesananService $service)
    {
        $this->pastikanMilik($request, $pesanan);

        $request->validate([
            'bukti' => ['required', 'image', 'max:2048'],
        ]);

        $pesanan->load('pembayaran');

        if ($pesanan->status !== 'menunggu_bayar' || ! in_array($pesanan->pembayaran?->status, ['menunggu_bukti', 'ditolak'], true)) {
            abort(422, 'Pesanan ini tidak menunggu bukti pembayaran.');
        }

        $path = $request->file('bukti')->store('bukti', 'public');

        if ($pesanan->pembayaran->bukti_path) {
            Storage::disk('public')->delete($pesanan->pembayaran->bukti_path);
        }

        $pesanan->pembayaran->bukti_path = $path;
        $pesanan->pembayaran->metode = 'manual';
        $pesanan->pembayaran->status = 'menunggu_verifikasi';
        $pesanan->pembayaran->catatan = null;
        $pesanan->pembayaran->save();

        $pesanan->status = 'menunggu_verifikasi';
        $pesanan->save();

        return response()->json(['data' => $service->susun($pesanan)]);
    }

    public function midtrans(Request $request, Pesanan $pesanan)
    {
        $this->pastikanMilik($request, $pesanan);
        $serverKey = config('services.midtrans.server_key');
        if (! $serverKey || ! config('services.midtrans.client_key')) {
            abort(422, 'Isi MIDTRANS_SERVER_KEY dan MIDTRANS_CLIENT_KEY di api/.env.');
        }

        $pesanan->load('pembayaran', 'user');
        if ($pesanan->status !== 'menunggu_bayar') {
            abort(422, 'Pesanan ini tidak menunggu pembayaran.');
        }

        \Midtrans\Config::$serverKey = $serverKey;
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $orderId = $pesanan->kode.'-'.$pesanan->pembayaran->id;
        $token = \Midtrans\Snap::getSnapToken([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) round((float) $pesanan->total),
            ],
            'customer_details' => [
                'first_name' => $pesanan->user->name,
                'email' => $pesanan->user->email,
                'phone' => $pesanan->user->telepon,
            ],
        ]);

        $pesanan->pembayaran->metode = 'midtrans';
        $pesanan->pembayaran->midtrans_order_id = $orderId;
        $pesanan->pembayaran->snap_token = $token;
        $pesanan->pembayaran->save();

        return response()->json([
            'snap_token' => $token,
            'client_key' => config('services.midtrans.client_key'),
        ]);
    }

    public function midtransCek(Request $request, Pesanan $pesanan, PesananService $service)
    {
        $this->pastikanMilik($request, $pesanan);
        $this->sinkronMidtrans($pesanan);

        return response()->json(['data' => $service->susun($pesanan->refresh())]);
    }

    public function notifikasiMidtrans(Request $request, PesananService $service)
    {
        $serverKey = config('services.midtrans.server_key');
        $orderId = (string) $request->input('order_id');
        $signature = hash('sha512', $orderId.$request->input('status_code').$request->input('gross_amount').$serverKey);

        if (! hash_equals($signature, (string) $request->input('signature_key'))) {
            abort(403, 'Signature tidak valid.');
        }

        $pembayaran = \App\Models\Pembayaran::query()->where('midtrans_order_id', $orderId)->firstOrFail();
        $this->tandaiLunasJikaBerhasil($pembayaran->pesanan, (string) $request->input('transaction_status'));

        return response()->json(['data' => $service->susun($pembayaran->pesanan->refresh())]);
    }

    private function sinkronMidtrans(Pesanan $pesanan): void
    {
        if (! $pesanan->pembayaran?->midtrans_order_id) {
            abort(422, 'Pembayaran Midtrans belum dibuat.');
        }

        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = (bool) config('services.midtrans.is_production');
        $status = \Midtrans\Transaction::status($pesanan->pembayaran->midtrans_order_id);
        $this->tandaiLunasJikaBerhasil($pesanan, $status->transaction_status);
    }

    private function tandaiLunasJikaBerhasil(Pesanan $pesanan, string $status): void
    {
        if (! in_array($status, ['capture', 'settlement'], true)) {
            return;
        }

        $pesanan->pembayaran->status = 'lunas';
        $pesanan->pembayaran->save();
        $pesanan->status = 'lunas';
        $pesanan->save();
        $pesanan->penumpang()->where('status', 'ditahan')->update(['status' => 'terjual']);
    }

    private function pastikanMilik(Request $request, Pesanan $pesanan): void
    {
        if ($request->user()->role !== 'admin' && $pesanan->user_id !== $request->user()->id) {
            abort(404);
        }
    }
}
