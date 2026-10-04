<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'potongan' => 'decimal:2',
            'batas_bayar_at' => 'datetime',
            'naik_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jadwal(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class);
    }

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }

    public function kelasPulang(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelas_pulang_id');
    }

    public function jadwalPulang(): BelongsTo
    {
        return $this->belongsTo(Jadwal::class, 'jadwal_pulang_id');
    }

    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function penumpang(): HasMany
    {
        return $this->hasMany(Penumpang::class);
    }

    public function pembayaran(): HasOne
    {
        return $this->hasOne(Pembayaran::class)->latestOfMany();
    }
}
