<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['kode', 'nama'])]
class Moda extends Model
{
    protected $table = 'moda';

    public function kereta(): HasMany
    {
        return $this->hasMany(Kereta::class);
    }
}
