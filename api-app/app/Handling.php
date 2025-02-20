<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Handling extends Model
{
    use HasFactory;

    protected $table = 'mst_handling';

    protected $fillable = [
        'id',
        'greige_id',
        'name',
        'lebar_preset',
        'lebar_finish',
        'berat_finish',
        'densiti_lusi',
        'densiti_pakan',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'buyer_ids',
        'keterangan',
        'no_hanger',
        'ket_washing',
        'ket_wr',
        'berat_persiapan',
    ];
}