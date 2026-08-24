<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DokumenLaporanKegiatan extends Model
{
    use HasFactory;

    protected $table = 'dokumen_laporan_kegiatan';
    protected $fillable = ['laporan_id', 'nama_dokumen', 'file_dokumen', 'status_validasi', 'catatan_revisi'];

    // Daftar dokumen standar untuk checklist "Tambah Dokumen" (Tim Kerja)
    const DOKUMEN_STANDAR = [
        'SK Tim Monev',
        'Undangan / Pemberitahuan Kegiatan',
        'Daftar Hadir',
        'Hasil Monitoring dan Evaluasi',
        'Dokumentasi',
        'Surat Tugas',
        'Laporan Perjalanan Dinas',
    ];

    public function laporan()
    {
        return $this->belongsTo(LaporanKegiatan::class, 'laporan_id');
    }

    public function isLocked(): bool
    {
        return $this->status_validasi === 'disetujui';
    }
}
