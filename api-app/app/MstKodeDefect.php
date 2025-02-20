<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MstKodeDefect extends Model
{
    protected $table = 'mst_kode_defect';

    protected $fillable = [
        'kode',
        'no_urut',
        'nama_defect',
        'asal_defect',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
    ];
}