<?php

namespace App;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KartuProsesDyeing extends Model
{
    // use HasFactory;

    public $table = "trn_kartu_proses_dyeing";

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'kartu_proses_id',
        'no_urut',
        'no',
        'asal_greige',
        'dikerjakan_oleh',
        'lusi',
        'pakan',
        'note',
        'date',
        'posted_at',
        'approved_at',
        'approved_by',
        'delivered_at',
        'delivered_by',
        'reject_notes',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'memo_pg',
        'memo_pg_at',
        'memo_pg_by',
        'memo_pg_no',
        'berat',
        'lebar',
        'k_density_lusi',
        'k_density_pakan',
        'lebar_preset',
        'lebar_finish',
        'berat_finish',
        't_density_lusi',
        't_density_pakan',
        'handling',
        'hasil_tes_gosok',
        'wo_color_id',
        'no_limit_item',
        'nomor_kartu',
        'tunggu_marketing',
        'toping_matching',
        'date_toping_matching',
        'is_redyeing'
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }

    public function woColor(): BelongsTo
    {
        return $this->belongsTo(WoColor::class, 'wo_color_id', 'id');
    }

    public function kartuProsesDyeingItem(): HasMany
    {
        return $this->hasMany(KartuProsesDyeingItem::class, 'kartu_process_id', 'id');
    }

    public function kartuProsesDyeingProcesses()
    {
        return $this->hasMany(KartuProcessDyeingProcess::class, 'kartu_process_id', 'id');
    }

    public function inspectings()
    {
        return $this->hasMany(Inspecting::class, 'kartu_process_dyeing_id', 'id');
    }


}
