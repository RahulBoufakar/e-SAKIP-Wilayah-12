<?php

namespace App\Models;

use App\Models\Concerns\HasStatusPengiriman;
use Illuminate\Database\Eloquent\Model;

class CapaianKinerja extends Model
{
    use HasStatusPengiriman;

    protected $table = 'capaian_kinerja';
    protected $fillable = ['iku_id', 'triwulan_id', 'tahun_anggaran_id', 'target', 'realisasi', 'status', 'catatan_revisi'];

    protected $casts = [
        'target' => 'decimal:2',
        'realisasi' => 'decimal:2',
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function triwulan()
    {
        return $this->belongsTo(Triwulan::class);
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function getCapaianAttribute(): ?float
    {
        if ($this->target === null || (float) $this->target == 0.0 || $this->realisasi === null) {
            return null;
        }

        return round(((float) $this->realisasi / (float) $this->target) * 100, 2);
    }
}