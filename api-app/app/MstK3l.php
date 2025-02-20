<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstK3l extends Model
{
    // use HasFactory;

    protected $table = 'mst_k3l';

    protected $fillable = [
        'k3l_code',
        'k3l_desc',
        'k3l_active',
        'k3l_add_by',
        'k3l_add_date',
        'k3l_upd_by',
        'k3l_upd_date'
    ];
}
