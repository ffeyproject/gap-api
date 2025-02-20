<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WoMemo extends Model
{
    use HasFactory;

    protected $table = 'trn_wo_memo';

    protected $fillable = [
        'wo_id',
        'memo',
        'created_at',
        'no_urut',
        'no',
        'tahun',
    ];

    protected $casts = [
        'created_at' => 'integer',
        'no_urut' => 'bigInteger',
        'tahun' => 'integer',
    ];

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class, 'wo_id', 'id');
    }
}