<?php
/**
 * Created by Antigravity.
 * Model for table trn_stock_greige_opname
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

class StockGreigeOpname extends Model
{
    protected $table = 'trn_stock_greige_opname';
    protected $primaryKey = 'id';

    protected $fillable = [
        'greige_group_id',
        'greige_id',
        'asal_greige',
        'no_lapak',
        'grade',
        'lot_lusi',
        'lot_pakan',
        'no_set_lusi',
        'panjang_m',
        'status_tsd',
        'no_document',
        'pengirim',
        'mengetahui',
        'note',
        'status',
        'date',
        'jenis_gudang',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'nomor_wo',
        'keputusan_qc',
        'color',
        'pfp_jenis_gudang',
        'is_pemotongan',
        'is_hasil_mix',
        'trans_from',
        'id_from',
        'qr_code',
        'stock_greige_id',
    ];

    protected $casts = [
        'panjang_m' => 'float',
        'is_pemotongan' => 'boolean',
        'is_hasil_mix' => 'boolean',
        'date' => 'date',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function greigeGroup()
    {
        return $this->belongsTo(GreigeGroup::class, 'greige_group_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function greige()
    {
        return $this->belongsTo(Greige::class, 'greige_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function stockGreige()
    {
        return $this->belongsTo(StockGreige::class, 'stock_greige_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
