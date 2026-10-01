<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 8 (Fasilitasi Penelitian/Publikasi/PkM/Kemitraan) — Spek
 * §4.3 tabel 9. Denominator realisasi memakai jumlah_publikasi (§4.2), BUKAN
 * jumlah_pts — beda dari IKU 4/5/6 yang serupa. Bukti dukung wajib.
 */
class CapaianFasilitasiPenelitian extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_fasilitasi_penelitian';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id', 'nidn', 'dosen_perwakilan', 'bentuk_fasilitasi', 'output',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi', 'sumber_baris_id',
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
