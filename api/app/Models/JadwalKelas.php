<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class JadwalKelas extends Model
{
    protected $table = 'jadwal_kelas';

    protected function casts(): array
    {
        return [
            'harga' => 'decimal:2',
        ];
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
