<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LaporanKegiatan extends Model
{
    protected $table = 'laporan_kegiatan';
    protected $fillable = ['proker_id', 'is_locked'];

    protected $casts = [
        'is_locked' => 'boolean',
    ];

    public function proker()
    {
        return $this->belongsTo(ProgramKerja::class, 'proker_id');
    }

    public function dokumen()
    {
        return $this->hasMany(DokumenLaporanKegiatan::class, 'laporan_id');
    }

    /** Semua dokumen sudah disetujui (dan minimal ada satu dokumen). */
    public function getSemuaDokumenDisetujuiAttribute(): bool
    {
        return $this->dokumen->isNotEmpty()
            && $this->dokumen->every(fn ($d) => $d->status_validasi === 'disetujui');
    }
}