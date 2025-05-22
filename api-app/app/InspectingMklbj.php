<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InspectingMklbj extends Model
{
    // use HasFactory;

    protected $table = 'inspecting_mkl_bj';

    public $timestamps = false;

    protected $fillable = [
        'wo_id',
        'wo_color_id',
        'tgl_inspeksi',
        'tgl_kirim',
        'no_lot',
        'jenis',
        'satuan',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'status',
        'no_urut',
        'no',
        'delivered_at',
        'delivered_by',
        'delivery_reject_note',
        'k3l_code',
        'defect',
        'inspection_table',
        'jenis_inspek',
    ];

    public function wo()
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }

    public function woColor()
    {
        return $this->belongsTo(WoColor::class, 'wo_color_id', 'id');
    }


    public function inspectingMklbjItem(): HasMany
    {
        return $this->hasMany(InspectingMklbjItem::class, 'inspecting_id');
    }
}