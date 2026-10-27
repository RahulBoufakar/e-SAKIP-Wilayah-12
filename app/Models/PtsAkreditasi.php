<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PtsAkreditasi extends Model
{
    protected $table = 'pts_akreditasi';

    protected $fillable = [
        'pts_id', 'peringkat', 'no_sk', 'tgl_sk', 'tgl_akhir', 'status_sk', 'terakhir_sinkron_at',
    ];

    protected $casts = [
        'tgl_sk' => 'date',
        'tgl_akhir' => 'date',
        'terakhir_sinkron_at' => 'datetime',
    ];

    public function pts(): BelongsTo
    {
        return $this->belongsTo(Pts::class);
    }
}