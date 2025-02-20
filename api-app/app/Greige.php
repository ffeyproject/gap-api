<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Greige extends Model
{
    // use HasFactory;

    protected $table = 'mst_greige';

    protected $fillable = [
        'id',
        'group_id',
        'nama_kain',
        'alias',
        'no_dok_referensi',
        'gap',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'aktif',
        'stock',
        'booked',
        'stock_pfp',
        'booked_pfp',
        'stock_wip',
        'booked_wip',
        'stock_ef',
        'booked_ef',
        'available',
        'booked_wo',
        'booked_opfp',
        'available_pfp',
    ];

    public function GreigeGroup(): BelongsTo
    {
        return $this->belongsTo(GreigeGroup::class, 'group_id');
    }
}