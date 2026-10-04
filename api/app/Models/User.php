<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'telepon', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'hp_verified_at' => 'datetime',
            'whatsapp_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sudahVerifikasi(): bool
    {
        return $this->email_verified_at && $this->hp_verified_at && $this->whatsapp_verified_at;
    }

    public function toProfil(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'telepon' => $this->telepon,
            'role' => $this->role,
            'email_terverifikasi' => (bool) $this->email_verified_at,
            'hp_terverifikasi' => (bool) $this->hp_verified_at,
            'whatsapp_terverifikasi' => (bool) $this->whatsapp_verified_at,
        ];
    }
}
