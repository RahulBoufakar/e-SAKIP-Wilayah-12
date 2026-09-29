<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 9 (Nilai Kinerja Anggaran RKA-K/L) — Spek §4.3 tabel 10.
 * Realisasi = nilai_rka pada baris disetujui TERBARU, tanpa pembagian
 * (§5 Pola 6, §11 poin 2 — diperlakukan sama seperti IKU 1: idealnya 1 baris
 * per triwulan, tapi query tetap ambil yang terbaru untuk jaga-jaga revisi).
 */
class CapaianNilaiRka extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_nilai_rka';
    protected $fillable = [
        'capaian_kinerja_id', 'nilai_rka', 'file_bukti_dukung', 'status_validasi', 'catatan_revisi',
    ];

    protected $casts = ['nilai_rka' => 'decimal:2'];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }
}
