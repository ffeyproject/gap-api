<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Inspecting extends Model
{
    // use HasFactory;

    protected $table = 'trn_inspecting';

    protected $fillable = [
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'kartu_process_dyeing_id',
        'jenis_process',
        'no_urut',
        'no',
        'date',
        'tanggal_inspeksi',
        'no_lot',
        'kombinasi',
        'note',
        'status',
        'unit',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'approved_at',
        'approved_by',
        'approval_reject_note',
        'delivered_at',
        'delivered_by',
        'delivery_reject_note',
        'kartu_process_printing_id',
        'memo_repair_id',
        'k3l_code',
        'defect',
        'inspection_table',
        'jenis_inspek',
    ];

    public $timestamps = false;

    public function sc()
    {
        return $this->belongsTo(Sc::class, 'sc_id', 'id');
    }

    public function scGreige()
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id', 'id');
    }

    /**
     * Belongs to a single Mo (Manufacturing Order)
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function mo()
    {
        return $this->belongsTo(Mo::class, 'mo_id', 'id');
    }

    public function wo()
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }

    public function kartuProcessDyeing()
    {
        return $this->belongsTo(KartuProsesDyeing::class, 'kartu_process_dyeing_id', 'id');
    }

    public function kartuProcessPrinting()
    {
        return $this->belongsTo(KartuProsesPrinting::class, 'kartu_process_printing_id', 'id');
    }

    public function memoRepair()
    {
        return $this->belongsTo(MemoRepair::class, 'memo_repair_id', 'id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by', 'id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by', 'id');
    }

    public function deliveredBy()
    {
        return $this->belongsTo(User::class, 'delivered_by', 'id');
    }

    public function k3l()
    {
        return $this->belongsTo(MstK3l::class, 'k3l_code', 'k3l_code');
    }

    public function inspectingItem(): HasMany
    {
        return $this->hasMany(InspectingItem::class);
    }


}