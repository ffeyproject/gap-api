<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WoColor extends Model
{
    // use HasFactory;

    protected $table = "trn_wo_color";

    protected $fillable = [
        'id',
        'sc_id',
        'sc_greige_id',
        'mo_id',
        'wo_id',
        'greige_id',
        'mo_color_id',
        'qty',
        'note',
        'ready_colour',
        'date_ready_colour',
    ];

    // Relationships
    public function sc(): BelongsTo
    {
        return $this->belongsTo(Sc::class);
    }

    public function scGreige(): BelongsTo
    {
        return $this->belongsTo(ScGreige::class);
    }

    public function greige(): BelongsTo
    {
        return $this->belongsTo(Greige::class);
    }

    public function mo(): BelongsTo
    {
        return $this->belongsTo(Mo::class);
    }

    public function moColor(): BelongsTo
    {
        return $this->belongsTo(MoColor::class, 'mo_color_id');
    }

    public function wo(): BelongsTo
    {
        return $this->belongsTo(Wo::class);
    }

    public function kartuProsesDyeings(): HasMany
    {
        return $this->hasMany(KartuProsesDyeing::class, 'wo_color_id');
    }

    public function kartuProsesPrintings(): HasMany
    {
        return $this->hasMany(KartuProsesPrinting::class, 'wo_color_id');
    }
}
