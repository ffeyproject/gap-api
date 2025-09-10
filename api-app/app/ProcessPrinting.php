<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProcessPrinting extends Model
{
    protected $table = 'mst_process_printing';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $fillable = [
        'order', 'max_pengulangan', 'nama_proses', 'tanggal', 'start', 'stop', 'no_mesin', 'operator', 'temp',
        'speed_depan', 'speed_belakang', 'speed', 'resep', 'density', 'jumlah_pcs', 'lebar_jadi', 'panjang_jadi',
        'info_kualitas', 'gangguan_produksi', 'created_at', 'created_by', 'updated_at', 'updated_by', 'over_feed',
    ];

    public function kartuProsesPrintingProcess()
    {
        return $this->hasMany(KartuProcessPrintingProcess::class, 'process_id', 'id');
    }
}
