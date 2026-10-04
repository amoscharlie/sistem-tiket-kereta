<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['kode', 'nama', 'kota'])]
class Stasiun extends Model
{
    protected $table = 'stasiun';
}
