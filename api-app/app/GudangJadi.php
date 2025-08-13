<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GudangJadi extends Model
{
    protected $table = 'trn_gudang_jadi';

    protected $fillable = [
        'jenis_gudang',
        'wo_id',
        'source',
        'source_ref',
        'unit',
        'qty',
        'no_urut',
        'no',
        'date',
        'status',
        'note',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'color',
        'no_memo_repair',
        'no_memo_ganti_greige',
        'grade',
        'hasil_pemotongan',
        'dipotong',
        'qr_code',
        'qr_code_desc',
        'qr_print_at',
        'locs_code',
        'status_packing',
        'trans_from',
        'id_from'
    ];

    public function wo()
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }

    public function gudangJadiMutasiItems()
    {
        return $this->hasMany(GudangJadiMutasiItem::class, 'stock_id', 'id');
    }

    public function mutasiExFinishAltItems()
    {
        return $this->hasMany(MutasiExFinishAltItem::class, 'gudang_jadi_id', 'id');
    }

    public function kirimBuyerItems()
    {
        return $this->hasMany(KirimBuyerItem::class, 'stock_id', 'id');
    }

    public function kirimMakloonItems()
    {
        return $this->hasMany(KirimMakloonItem::class, 'stock_id', 'id');
    }

    public function potongStocks()
    {
        return $this->hasMany(PotongStock::class, 'stock_id', 'id');
    }

    public function wmsOpnamedDetails()
    {
        return $this->hasMany(WmsOpnamedDetail::class, 'opnamed_id_stok', 'id');
    }

    public function wmsSetLocationDtls()
    {
        return $this->hasMany(WmsSetLocationDtl::class, 'setd_id_stok', 'id');
    }
}