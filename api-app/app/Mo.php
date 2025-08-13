<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mo extends Model
{
    // use HasFactory;

    protected $table = 'trn_mo';

    protected $fillable = [
        'id',
        'sc_id',
        'sc_greige_id',
        'process',
        'approval_id',
        'approved_at',
        'no_urut',
        'no',
        'date',
        're_wo',
        'design',
        'article',
        'strike_off',
        'heat_cut',
        'sulam_pinggir',
        'border_size',
        'block_size',
        'foil',
        'face_stamping',
        'selvedge_stamping',
        'selvedge_continues',
        'side_band',
        'tag',
        'hanger',
        'label',
        'folder',
        'album',
        'joint',
        'joint_qty',
        'packing_method',
        'shipping_method',
        'shipping_sorting',
        'plastic',
        'arsip',
        'jet_black',
        'piece_length',
        'est_produksi',
        'est_packing',
        'target_shipment',
        'jenis_gudang',
        'posted_at',
        'closed_at',
        'closed_by',
        'closed_note',
        'reject_notes',
        'batal_at',
        'batal_by',
        'batal_note',
        'status',
        'note',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'handling',
        'no_lab_dip',
        'no_po',
        'persen_grading',
    ];

    public $timestamps = false;

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id');
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id');
    }

    public function approval(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approval_id');
    }

    public function batalBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'batal_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function wo()
    {
        return $this->hasMany(Wo::class, 'mo_id');
    }
}