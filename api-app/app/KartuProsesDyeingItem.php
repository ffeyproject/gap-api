<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuProsesDyeingItem extends Model
{
    // use HasFactory;

    protected $table = 'trn_kartu_proses_dyeing_item';

    protected $fillable = [
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'kartu_process_id',
        'stock_id',
        'panjang_m',
        'mesin',
        'tube',
        'note',
        'status',
        'date',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function kartuProses(): BelongsTo
    {
        return $this->belongsTo(KartuProsesDyeing::class, 'kartu_process_id', 'id');
    }

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class, 'mo_id', 'id');
    }

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id', 'id');
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id', 'id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(StockGreige::class, 'stock_id', 'id');
    }

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }
}
