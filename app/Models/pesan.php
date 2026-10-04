<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class pesan extends Model
{
    use HasFactory;

    protected $table = 'pesan';

    protected $fillable =['nama','tanggal','id_nominal','status','waktu'];

    public function nominal()
    {
        return $this->belongsTo(nominal::class, 'id_nominal');
    }
}