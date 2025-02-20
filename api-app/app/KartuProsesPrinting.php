<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuProsesPrinting extends Model
{
    protected $table = 'trn_kartu_proses_printing';

    public $timestamps = false;

    protected $fillable = [
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'kartu_proses_id',
        'no_urut',
        'no',
        'no_proses',
        'asal_greige',
        'dikerjakan_oleh',
        'lusi',
        'pakan',
        'note',
        'date',
        'posted_at',
        'approved_at',
        'approved_by',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'memo_pg',
        'memo_pg_at',
        'memo_pg_by',
        'memo_pg_no',
        'delivered_at',
        'delivered_by',
        'reject_notes',
        'wo_color_id',
        'kombinasi',
        'berat',
        'no_limit_item',
        'nomor_kartu',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class, 'mo_id', 'id');
    }

    public function moColor(): BelongsTo
    {
        return $this->belongsTo(MoColor::class, 'mo_color_id', 'id');
    }

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id', 'id');
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id', 'id');
    }

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }

    public function woColor(): BelongsTo
    {
        return $this->belongsTo(WoColor::class, 'wo_color_id', 'id');
    }

    public function kartuProsesPrintingItem(): HasMany
    {
        return $this->hasMany(KartuProsesPrintingItem::class, 'kartu_process_id', 'id');
    }
}