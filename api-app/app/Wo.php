<?php

namespace App;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wo extends Model
{
    // use HasFactory;

    public $table = "trn_wo";

    protected $fillable = [
        'id',
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'jenis_order',
        'greige_id',
        'mengetahui_id',
        'apv_mengetahui_at',
        'reject_note_mengetahui',
        'no_urut',
        'no',
        'date',
        'plastic_size',
        'shipping_mark',
        'note',
        'note_two',
        'marketing_id',
        'apv_marketing_at',
        'reject_note_marketing',
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
        'handling_id',
        'papper_tube_id',
        'tgl_kirim',
        'validasi_stock',
    ];

    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class, 'sc_id');
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class, 'sc_greige_id');
    }

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class, 'mo_id');
    }

    public function greige(): BelongsTo
    {
        return $this->belongsTo(Greige::class, 'greige_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mengetahui_id');
    }

    public function marketing(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
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

    public function marketingBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marketing_id');
    }

    public function mengetahuiBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'mengetahui_id');
    }

    public function handling(): BelongsTo
    {
        return $this->belongsTo(Handling::class, 'handling_id');
    }

    public function papperTube(): BelongsTo
    {
        return $this->belongsTo(MstPapperTube::class, 'papper_tube_id');
    }

    public function WoColor(): HasMany
    {
        return $this->hasMany(WoColor::class, 'wo_id');
    }
}