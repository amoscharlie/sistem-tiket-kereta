<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penumpang extends Model
{
    protected $table = 'penumpang';

    public function kursi(): BelongsTo
    {
        return $this->belongsTo(Kursi::class);
    }
}
