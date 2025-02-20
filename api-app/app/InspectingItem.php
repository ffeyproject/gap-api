<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InspectingItem extends Model
{
    // use HasFactory;

    protected $table = 'inspecting_item';

    public $timestamps = false;

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    protected $fillable = [
        'inspecting_id',
        'grade',
        'join_piece',
        'qty',
        'note',
        'qty_sum',
        'is_head',
        'qty_count',
        'qr_code',
        'qr_code_desc',
        'qr_print_at',
        'grade_up',
        'lot_no',
        'defect',
        'stock_id',
        'qty_bit',
        'gsm_item',
    ];

    public function inspecting()
    {
        return $this->belongsTo(Inspecting::class, 'inspecting_id');
    }

    public function defect_item()
    {
        return $this->hasMany(DefectInspectingItem::class);
    }


    public function stock()
    {
        return $this->belongsTo(StockGreige::class, 'stock_id', 'id');
    }
}