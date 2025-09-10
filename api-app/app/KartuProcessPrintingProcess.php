<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KartuProcessPrintingProcess extends Model
{
    protected $table = 'kartu_process_printing_process';

    protected $primaryKey = ['kartu_process_id', 'process_id'];

    public $incrementing = false;

    protected $guarded = [];

    public function kartuProcess(): BelongsTo
    {
        return $this->belongsTo(KartuProsesPrinting::class, 'kartu_process_id', 'id');
    }

    public function process(): BelongsTo
    {
        return $this->belongsTo(ProcessPrinting::class, 'process_id', 'id');
    }
}