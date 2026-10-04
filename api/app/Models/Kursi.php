<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kursi extends Model
{
    protected $table = 'kursi';

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class);
    }
}
