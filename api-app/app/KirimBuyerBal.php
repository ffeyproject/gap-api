<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KirimBuyerBal extends Model
{
    use HasFactory;

    protected $table = 'trn_kirim_buyer_bal';

    protected $primaryKey = 'id';

    protected $fillable = [
        'trn_kirim_buyer_id',
        'no_bal',
        'header_id',
    ];

    public function kirimBuyer(): BelongsTo
    {
        return $this->belongsTo(KirimBuyer::class, 'trn_kirim_buyer_id', 'id');
    }

    public function header(): BelongsTo
    {
        return $this->belongsTo(KirimBuyerHeader::class, 'header_id', 'id');
    }
}