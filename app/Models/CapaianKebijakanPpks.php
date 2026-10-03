<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 5 (Kebijakan Anti Kekerasan/Narkoba/Korupsi) — Spek §4.3
 * tabel 6. Satu PTS dihitung "lengkap" hanya jika ketiga kolom implementasi
 * terisi (§5 Pola 4) — dicek di App\Services\CapaianKinerjaHitungService,
 * bukan di model ini (model murni data).
 */
class CapaianKebijakanPpks extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_kebijakan_ppks';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id',
        'file_implementasi_ppks_antinarkoba_antikorupsi', 'file_bukti_dukung',
        'status_validasi', 'catatan_revisi', 'sumber_baris_id',
    ];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function pts()
    {
        return $this->belongsTo(Pts::class);
    }
}
