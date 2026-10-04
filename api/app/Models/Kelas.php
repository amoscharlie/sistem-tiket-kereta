<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['nama', 'urutan'])]
class Kelas extends Model
{
    protected $table = 'kelas';
}
