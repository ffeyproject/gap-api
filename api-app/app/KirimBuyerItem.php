<?php

namespace App\Models;

use App\GudangJadi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KirimBuyerItem extends Model
{
    use HasFactory;

    protected $table = 'trn_kirim_buyer_item';

    protected $primaryKey = 'id';

    protected $fillable = [
        'kirim_buyer_id',
        'stock_id',
        'qty',
        'note',
        'no_bal',
        'bal_id'
    ];

    protected $hidden = [];

    public function kirimBuyer(): BelongsTo
    {
        return $this->belongsTo(KirimBuyer::class, 'kirim_buyer_id', 'id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(GudangJadi::class, 'stock_id', 'id');
    }
}