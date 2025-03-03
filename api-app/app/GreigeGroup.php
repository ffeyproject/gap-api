<?php

namespace App;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreigeGroup extends Model
{
    // use HasFactory;

    protected $table = 'mst_greige_group';

    protected $fillable = [
        'id',
        'jenis_kain',
        'nama_kain',
        'qty_per_batch',
        'unit',
        'nilai_penyusutan',
        'gramasi_kain',
        'sulam_pinggir',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'aktif',
        'lebar_kain',
    ];
}
