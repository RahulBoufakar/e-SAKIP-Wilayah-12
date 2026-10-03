<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 3.3 (Fasilitasi Penelitian/Publikasi/PkM/Kemitraan).
 * Denominator realisasi = jumlah_publikasi milik header (per triwulan), BUKAN
 * jumlah_pts. Pola baris sama dengan 3.1; bukti dukung opsional.
 */
class CapaianFasilitasiPenelitian extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_fasilitasi_penelitian';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', 'tanggal_kegiatan',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi', 'sumber_baris_id',
    ];

    protected $casts = ['tanggal_kegiatan' => 'date'];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function pts()
    {
        return $this->belongsTo(Pts::class);
    }
}
