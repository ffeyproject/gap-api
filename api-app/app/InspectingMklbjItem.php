<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectingMklbjItem extends Model
{
    // use HasFactory;

    protected $table = 'inspecting_mkl_bj_items';

    public $timestamps = false;

    protected $fillable = [
        'inspecting_id',
        'grade',
        'join_piece',
        'qty',
        'note',
        'is_head',
        'qty_sum',
        'qty_count',
        'qr_code',
        'qr_code_desc',
        'qr_print_at',
        'grade_up',
        'lot_no',
        'defect',
        'gsm_item',
        'no_urut'
    ];

    public function inspectingMklbj()
    {
        return $this->belongsTo(InspectingMklbj::class, 'inspecting_id');
    }

    public function defect_item()
    {
        return $this->hasMany(DefectInspectingItem::class);
    }
}