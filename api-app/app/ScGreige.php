<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScGreige extends Model
{
    // use HasFactory;

    protected $table = 'trn_sc_greige';

    protected $fillable = [
        'id',
        'sc_id',
        'greige_group_id',
        'process',
        'lebar_kain',
        'merek',
        'grade',
        'piece_length',
        'unit_price',
        'price_param',
        'qty',
        'woven_selvedge',
        'note',
        'closed',
        'closing_note',
        'no_order_greige',
        'no_urut_order_greige',
        'order_greige_note',
        'order_grege_approved',
        'order_grege_approved_at',
        'order_grege_approved_by',
        'order_grege_approval_note',
        'order_grege_approved_dir',
        'order_grege_approved_at_dir',
        'order_grege_approval_note_dir',
    ];

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id');
    }

    public function greigeGroup(): BelongsTo
    {
        return $this->belongsTo(GreigeGroup::class, 'greige_group_id');
    }
}