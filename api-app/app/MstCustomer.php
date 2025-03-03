<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstCustomer extends Model
{
    //use HasFactory;

    protected $table = 'mst_customer';

    protected $fillable = [
        'cust_no',
        'name',
        'telp',
        'fax',
        'email',
        'address',
        'cp_name',
        'cp_phone',
        'cp_email',
        'npwp',
        'aktif',
    ];
}