<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $table = 'voucher';

    protected function casts(): array
    {
        return [
            'nilai' => 'decimal:2',
            'berlaku_sampai' => 'date',
            'aktif' => 'boolean',
        ];
    }
}
