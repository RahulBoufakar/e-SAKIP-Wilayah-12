<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Realisasi extends Model
{
    protected $fillable = ['iku_id', 'triwulan', 'target', 'realisasi'];

    protected $casts = [
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
    ];

    public function iku(): BelongsTo
    {
        return $this->belongsTo(Iku::class);
    }

    public function getCapaianAttribute(): ?float
    {
        if ($this->target === null || (float) $this->target == 0.0 || $this->realisasi === null) {
            return null;
        }

        return round(((float) $this->realisasi / (float) $this->target) * 100, 2);
    }
}