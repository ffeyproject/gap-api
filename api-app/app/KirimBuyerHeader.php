<?php

namespace App\Models;

use App\MstVendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KirimBuyerHeader extends Model
{
    use HasFactory;

    protected $table = 'trn_kirim_buyer_header';

    protected $primaryKey = 'id';

    protected $fillable = [
        'customer_id',
        'date',
        'no_urut',
        'no',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'pengirim',
        'penerima',
        'kepala_gudang',
        'note',
        'nama_buyer',
        'alamat_buyer',
        'plat_nomor',
        'is_export',
        'is_resmi',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MstVendor::class, 'customer_id', 'id');
    }

    public function kirimBuyer(): HasMany
    {
        return $this->hasMany(KirimBuyer::class, 'header_id', 'id');
    }
}
