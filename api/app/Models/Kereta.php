<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['moda_id', 'kode', 'nama'])]
class Kereta extends Model
{
    protected $table = 'kereta';

    public function moda(): BelongsTo
    {
        return $this->belongsTo(Moda::class);
    }
}
