<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KodeVerifikasi extends Model
{
    protected $table = 'kode_verifikasi';

    protected function casts(): array
    {
        return [
            'kedaluwarsa_at' => 'datetime',
        ];
    }
}
