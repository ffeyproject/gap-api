<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DefectInspectingItem extends Model
{
    // use HasFactory;

    protected $table = 'defect_inspecting_items';

    protected $fillable = [
        'id',
        'inspecting_item_id',
        'inspecting_mklbj_item_id',
        'mst_kode_defect_id',
        'meterage',
        'point',
    ];

    public function inspectingItem(): BelongsTo
    {
        return $this->belongsTo(InspectingItem::class, 'inspecting_item_id');
    }

    public function mstKodeDefect()
    {
        return $this->belongsTo(MstKodeDefect::class, 'mst_kode_defect_id');
    }
}