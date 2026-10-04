<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jadwal extends Model
{
    protected $table = 'jadwal';

    protected function casts(): array
    {
        return [
            'berangkat_at' => 'datetime',
            'tiba_at' => 'datetime',
        ];
    }

    public function kereta(): BelongsTo
    {
        return $this->belongsTo(Kereta::class);
    }

    public function asal(): BelongsTo
    {
        return $this->belongsTo(Stasiun::class, 'stasiun_asal_id');
    }

    public function tujuan(): BelongsTo
    {
        return $this->belongsTo(Stasiun::class, 'stasiun_tujuan_id');
    }

    public function hargaKelas(): HasMany
    {
        return $this->hasMany(JadwalKelas::class);
    }

    public function kursi(): HasMany
    {
        return $this->hasMany(Kursi::class);
    }
}
