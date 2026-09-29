<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/** Baris detail IKU 2 komponen Penggabungan — Spek §4.3 tabel 3. */
class CapaianPenggabunganPts extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_penggabungan_pts';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id', 'sk_penggabungan',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi',
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
