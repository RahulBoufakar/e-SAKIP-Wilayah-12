<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/** Baris detail IKU 6 (Fasilitasi Kemahasiswaan) — Spek §4.3 tabel 7. Bukti dukung wajib. */
class CapaianFasilitasiKemahasiswaan extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_fasilitasi_kemahasiswaan';
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
