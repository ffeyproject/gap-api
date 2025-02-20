<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MoMemo extends Model
{
    use HasFactory;

    protected $table = 'trn_mo_memo';

    protected $fillable = [
        'id',
        'mo_id',
        'memo',
    ];

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class, 'mo_id', 'id');
    }
}