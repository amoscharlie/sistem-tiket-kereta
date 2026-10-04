<?php

namespace App\Http\Controllers;

use App\Models\KodeVerifikasi;
use Illuminate\Http\Request;

class VerifikasiController extends Controller
{
    public function kirim(Request $request)
    {
        $data = $request->validate([
            'kanal' => ['required', 'in:email,hp,whatsapp'],
        ]);

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        KodeVerifikasi::query()
            ->where('user_id', $request->user()->id)
            ->where('kanal', $data['kanal'])
            ->delete();

        $baris = new KodeVerifikasi;
        $baris->user_id = $request->user()->id;
        $baris->kanal = $data['kanal'];
        $baris->kode = $kode;
        $baris->kedaluwarsa_at = now()->addMinutes(10);
        $baris->save();

        return response()->json([
            'kanal' => $data['kanal'],
            'kode' => $kode,
            'pesan' => 'Mode percobaan. Kode ini tidak dikirim sungguhan.',
        ]);
    }

    public function konfirmasi(Request $request)
    {
        $data = $request->validate([
            'kanal' => ['required', 'in:email,hp,whatsapp'],
            'kode' => ['required', 'digits:6'],
        ]);

        $baris = KodeVerifikasi::query()
            ->where('user_id', $request->user()->id)
            ->where('kanal', $data['kanal'])
            ->where('kode', $data['kode'])
            ->where('kedaluwarsa_at', '>', now())
            ->first();

        if (! $baris) {
            abort(422, 'Kode tidak sesuai atau sudah kedaluwarsa.');
        }

        $kolom = [
            'email' => 'email_verified_at',
            'hp' => 'hp_verified_at',
            'whatsapp' => 'whatsapp_verified_at',
        ][$data['kanal']];

        $user = $request->user();
        $user->{$kolom} = now();
        $user->save();
        $baris->delete();

        return response()->json(['user' => $user->fresh()->toProfil()]);
    }
}
