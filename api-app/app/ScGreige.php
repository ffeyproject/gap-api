<?php

namespace App;

use App\Helpers\Converter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScGreige extends Model
{
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

    // ----------------------------------------------------
    //  FUNGSI DARI YII2 YANG DIADAPTASI KE LARAVEL
    // ----------------------------------------------------

    public function getQtyBatchToMeter()
    {
        if (!$this->greigeGroup) {
            return (float) $this->qty; // fallback
        }

        switch ($this->greigeGroup->unit) {
            case GreigeGroup::UNIT_YARD:
                return Converter::yardToMeter($this->getQtyBatchToUnit());
            case GreigeGroup::UNIT_METER:
                return $this->getQtyBatchToUnit();
            case GreigeGroup::UNIT_KILOGRAM:
                return 0;
            default:
                return (float) $this->qty; // fallback
        }
    }

    public function getQtyBatchToYard()
    {
        if (!$this->greigeGroup) {
            return (float) $this->qty;
        }

        switch ($this->greigeGroup->unit) {
            case GreigeGroup::UNIT_YARD:
                return $this->getQtyBatchToUnit();
            case GreigeGroup::UNIT_METER:
                return Converter::meterToYard($this->getQtyBatchToUnit());
            case GreigeGroup::UNIT_KILOGRAM:
                return 0;
            default:
                return (float) $this->qty;
        }
    }

     // =====================
    // === QTY FUNCTIONS ===
    // =====================

   public function getQtyFinish()
    {
        if (!$this->greigeGroup) {
            return (float) $this->qty;
        }

        $perBatch = (float) ($this->greigeGroup->qty_per_batch ?? 1);
        $susut    = (float) ($this->greigeGroup->nilai_penyusutan ?? 0);
        $qty      = (float) $this->qty;

        // Rumus: qty × perBatch × (1 - susut/100)
        $hasil = $qty * $perBatch * (1 - ($susut / 100));

        return round($hasil, 2);
    }

    public function getQtyFinishToYard()
    {
        if (!$this->greigeGroup) {
            return (float) $this->qty;
        }

        $perBatch = (float) ($this->greigeGroup->qty_per_batch ?? 1);
        $susut    = (float) ($this->greigeGroup->nilai_penyusutan ?? 0);
        $qty      = (float) $this->qty;
        $yardConv = 1.093613298; // Konversi akurat 1 meter → yard

        // Rumus: qty × perBatch × (1 - susut/100) × konversi yard
        $hasil = $qty * $perBatch * (1 - ($susut / 100)) * $yardConv;

        return round($hasil, 2);
    }

    // Tambahkan placeholder method agar tidak error
    protected function getQtyBatchToUnit()
    {
        return $this->qty ?? 0;
    }


}
