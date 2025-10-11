<?php

namespace App\Models;

use App\Mo;
use App\Sc;
use App\ScGreige;
use App\Wo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KirimBuyer extends Model
{
    use HasFactory;

    protected $table = 'trn_kirim_buyer';

    protected $primaryKey = 'id';

    public $incrementing = false;

    protected $fillable = [
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'nama_kain_alias',
        'unit',
        'note',
        'header_id',
    ];

    public function header(): BelongsTo
    {
        return $this->belongsTo(KirimBuyerHeader::class, 'header_id', 'id');
    }

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id', 'id');
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id', 'id');
    }

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class, 'mo_id', 'id');
    }

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }
}
