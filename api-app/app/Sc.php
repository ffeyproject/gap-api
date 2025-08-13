<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sc extends Model
{
    // use HasFactory;

    protected $table = 'trn_sc';

    protected $fillable = [
        'id',
        'cust_id',
        'jenis_order',
        'currency',
        'bank_acct_id',
        'direktur_id',
        'manager_id',
        'marketing_id',
        'no_urut',
        'no',
        'tipe_kontrak',
        'date',
        'pmt_term',
        'pmt_method',
        'ongkos_angkut',
        'due_date',
        'delivery_date',
        'destination',
        'packing',
        'jet_black',
        'no_po',
        'disc_grade_b',
        'disc_piece_kecil',
        'consignee_name',
        'apv_dir_at',
        'reject_note_dir',
        'apv_mgr_at',
        'reject_note_mgr',
        'notify_party',
        'buyer_name_in_invoice',
        'note',
        'posted_at',
        'closed_at',
        'closed_by',
        'closed_note',
        'batal_at',
        'batal_by',
        'batal_note',
        'status',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MstCustomer::class, 'cust_id', 'id');
    }

    public function bankAcct(): BelongsTo
    {
        return $this->belongsTo(MstBankAccount::class, 'bank_acct_id');
    }

    public function direktur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'direktur_id');
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function mo()
    {
        return $this->hasMany(Mo::class, 'sc_id');
    }
}