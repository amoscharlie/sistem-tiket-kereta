<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function daftar(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:120', 'unique:users,email'],
            'telepon' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
            'password' => $data['password'],
            'role' => 'pelanggan',
        ]);

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->toProfil(),
        ], 201);
    }

    public function masuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user->toProfil(),
        ]);
    }

    public function keluar(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Keluar.']);
    }

    public function saya(Request $request)
    {
        return response()->json(['user' => $request->user()->toProfil()]);
    }
}
