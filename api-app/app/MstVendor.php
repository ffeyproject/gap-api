<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstVendor extends Model
{
    use HasFactory;

    protected $table = 'mst_vendor';

    protected $fillable = [
        'id',
        'name',
        'telp',
        'fax',
        'email',
        'address',
        'cp_name',
        'aktif'
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'telp' => 'string',
        'fax' => 'string',
        'email' => 'string',
        'address' => 'string',
        'cp_name' => 'string',
        'aktif' => 'boolean',
    ];

    protected $attributes = [
        'aktif' => true,
    ];
}