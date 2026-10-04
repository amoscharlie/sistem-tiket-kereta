<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class nominal extends Model
{
    use HasFactory;

    protected $table ='nominal';

    protected $fillable =['tarif'];

    public function pesan()
    {
        return $this->hasMany(pesan::class,'id_nominal');
    }
}
