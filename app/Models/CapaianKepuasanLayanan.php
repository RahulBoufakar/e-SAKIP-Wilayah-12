<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
* Baris detail IKU 1 (Kepuasan Layanan) — Spek §4.3 tabel 1. Entri tunggal per header.
* Realisasi = hasil_perhitungan_kepuasan (%, diinput langsung, maks 100) pada baris
* disetujui terbaru (App\Services\CapaianKinerjaHitungService). responden_puas sudah dihapus.
 */
class CapaianKepuasanLayanan extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_kepuasan_layanan';
    protected $fillable = [
        'capaian_kinerja_id', 'total_responden', 'hasil_perhitungan_kepuasan',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi',
    ];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }
}
