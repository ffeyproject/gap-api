<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MstBankAccount extends Model
{
    use HasFactory;

    protected $table = 'mst_bank_account';

    protected $fillable = [
        'bank_name',
        'acct_no',
        'acct_name',
        'swift_code',
        'address',
        'correspondence',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
}