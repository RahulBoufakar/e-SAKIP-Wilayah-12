<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SinkronisasiPts extends Model
{
    public const UPDATED_AT = null; // tabel hanya punya created_at

    protected $table = 'sinkronisasi_pts';

    protected $fillable = ['jenis', 'status', 'dipicu_oleh', 'ringkasan', 'catatan', 'mulai_at', 'selesai_at'];

    protected $casts = [
        'ringkasan' => 'array',
        'mulai_at' => 'datetime',
        'selesai_at' => 'datetime',
    ];

    public function pemicu(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dipicu_oleh');
    }

    public function scopeDiproses(Builder $query): Builder
    {
        return $query->where('status', 'diproses');
    }
}