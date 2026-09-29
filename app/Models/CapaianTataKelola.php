<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use App\Support\SkorSakipZi;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 3 (Tata Kelola SAKIP+ZI) — Spek §4.3 tabel 4.
 * Tingkat institusi (tanpa pts_id). Realisasi dihitung lewat
 * App\Support\SkorSakipZi::hitung(), bukan formula rasio.
 */
class CapaianTataKelola extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_tata_kelola';
    protected $fillable = [
        'capaian_kinerja_id', 'predikat_sakip', 'predikat_zi',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi',
    ];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function getPredikatSakipLabelAttribute(): string
    {
        return SkorSakipZi::label($this->predikat_sakip, SkorSakipZi::skorSakip($this->predikat_sakip));
    }

    public function getPredikatZiLabelAttribute(): string
    {
        return SkorSakipZi::label($this->predikat_zi, SkorSakipZi::skorZi($this->predikat_zi));
    }
}
