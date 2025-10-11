<?php

namespace App;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GreigeGroup extends Model
{
    // use HasFactory;

    /**
     * Nama tabel database
     *
     * @var string
     */
    protected $table = 'mst_greige_group';

    /**
     * Kolom yang boleh diisi (mass assignment)
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'jenis_kain',
        'nama_kain',
        'qty_per_batch',
        'unit',
        'nilai_penyusutan',
        'gramasi_kain',
        'sulam_pinggir',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'aktif',
        'lebar_kain',
    ];

    // -----------------------------------------------------
    //  KONSTANTA & OPSI UNIT (ADAPTASI DARI YII2)
    // -----------------------------------------------------

    const UNIT_YARD = 1;
    const UNIT_METER = 2;
    const UNIT_KILOGRAM = 3;
    const UNIT_PCS = 4;

    /**
     * Mengembalikan daftar pilihan unit yang valid
     *
     * @return array
     */
    public static function unitOptions()
    {
        return [
            self::UNIT_YARD => 'Yard',
            self::UNIT_METER => 'Meter',
            self::UNIT_KILOGRAM => 'Kilogram',
        ];
    }

    public function getQtyFinishAttribute()
    {
        // Jika kolom qty_finish ada di tabel, gunakan itu.
        // Jika tidak, gunakan fallback logika konversi.
        return $this->attributes['qty_finish']
            ?? ($this->qty_per_batch > 0 ? (float)$this->qty_per_batch / 100 : 1);
    }

    // -----------------------------------------------------
    //  RELASI
    // -----------------------------------------------------

    /**
     * Contoh relasi balik ke ScGreige (opsional)
     * Kalau nanti diperlukan:
     */
    public function scGreiges()
    {
        return $this->hasMany(ScGreige::class, 'greige_group_id');
    }

    // -----------------------------------------------------
    //  ACCESSOR TAMBAHAN (OPSIONAL)
    // -----------------------------------------------------

    /**
     * Mengembalikan label unit dengan nama yang lebih ramah
     *
     * @return string|null
     */
    public function getUnitLabelAttribute()
    {
        $options = self::unitOptions();
        return $options[$this->unit] ?? null;
    }

    /**
     * Contoh: apakah greige aktif atau tidak
     *
     * @return bool
     */
    public function getIsActiveAttribute()
    {
        return $this->aktif == 1;
    }
}