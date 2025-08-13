<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProcessDyeing extends Model
{
    protected $table = 'mst_process_dyeing';
    protected $primaryKey = 'id';
    protected $fillable = [
        'order',
        'max_pengulangan',
        'nama_proses',
        'tanggal',
        'start',
        'stop',
        'no_mesin',
        'shift_group',
        'temp',
        'speed',
        'gramasi',
        'program_number',
        'density',
        'over_feed',
        'lebar_jadi',
        'panjang_jadi',
        'info_kualitas',
        'gangguan_produksi',
        'created_by',
        'updated_by',
    ];

    public function kartuProcessCelupProcess()
    {
        return $this->hasMany(KartuProcessCelupProcess::class, 'process_id', 'id');
    }

    public function kartuProcessDyeingProcess()
    {
        return $this->hasMany(KartuProsesDyeingProcess::class, 'process_id', 'id');
    }
}