<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoColor extends Model
{
    // use HasFactory;

    protected $table = 'trn_mo_color';

    protected $fillable = [
        'id',
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'color',
        'qty',
    ];

    // Relationships
    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class);
    }

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class);
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class);
    }
}