<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ScAgen extends Model
{
    protected $table = 'trn_sc_agen';

    protected $fillable = [
        'sc_id',
        'date',
        'nama_agen',
        'attention',
        'no_urut',
        'no',
    ];

    public function sc()
    {
        return $this->belongsTo(Sc::class, 'sc_id', 'id');
    }
}