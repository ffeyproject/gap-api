<?php
namespace App;

use Illuminate\Database\Eloquent\Model;

class ActionLogKartuDyeing extends Model
{
    protected $table = 'action_log_kartu_dyeing';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'username',
        'kartu_proses_id',
        'action_name',
        'description',
        'ip',
        'user_agent',
        'created_at',
    ];
}