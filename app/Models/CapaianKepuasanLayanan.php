<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 1 (Kepuasan Layanan) — Spek §4.3 tabel 1.
 * Realisasi = SUM(responden_puas) / SUM(total_responden) x 100%, dihitung
 * dari baris berstatus disetujui saja (App\Services\CapaianKinerjaHitungService).
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
