<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstPapperTube extends Model
{
    use HasFactory;

    protected $table = 'mst_papper_tube';

    protected $fillable = [
        'id',
        'name',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
}