<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class KartuProcessDyeingProcess extends Model
{
    protected $table = 'kartu_process_dyeing_process';
    protected $primaryKey = ['kartu_process_id', 'process_id'];
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'kartu_process_id',
        'process_id',
        'value',
        'note',
    ];

    public function kartuProsesDyeing()
    {
        return $this->belongsTo(KartuProsesDyeing::class, 'kartu_process_id');
    }

    public function processDyeing()
    {
        return $this->belongsTo(ProcessDyeing::class, 'process_id');
    }

}