<?php

namespace App\Http\Controllers;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class GoogleController extends Controller
{
    public function redirect()
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return response(
                "GOOGLE_CLIENT_ID dan GOOGLE_CLIENT_SECRET belum diisi di api/.env\n",
                500,
                ['Content-Type' => 'text/plain; charset=UTF-8'],
            );
        }

        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        $google = Socialite::driver('google')->user();

        $user = User::query()->where('email', $google->getEmail())->first();
        if (! $user) {
            $user = new User;
            $user->name = $google->getName() ?: $google->getEmail();
            $user->email = $google->getEmail();
            $user->role = 'pelanggan';
            $user->password = null;
        }

        $user->google_id = $google->getId();
        $user->email_verified_at = $user->email_verified_at ?: now();
        $user->save();

        $token = $user->createToken('google')->plainTextToken;
        $tujuan = rtrim(env('FRONTEND_URL', 'http://127.0.0.1:3000'), '/').'/google?token='.urlencode($token);

        return redirect()->away($tujuan);
    }
}
