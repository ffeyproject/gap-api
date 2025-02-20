<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScKomisi extends Model
{
    use HasFactory;

    protected $table = 'trn_sc_komisi';

    protected $fillable = [
        'id',
        'sc_id',
        'sc_agen_id',
        'sc_greige_id',
        'tipe_komisi',
        'komisi_amount',
    ];

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id');
    }

    public function scAgen(): BelongsTo
    {
        return $this->belongsTo(ScAgen::class, 'sc_agen_id');
    }
}