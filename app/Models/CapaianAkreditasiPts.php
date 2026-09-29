<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/** Baris detail IKU 2 komponen Akreditasi — Spek §4.3 tabel 2. */
class CapaianAkreditasiPts extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_akreditasi_pts';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id', 'akreditasi', 'no_sk', 'masa_berlaku',
        'file_bukti_dukung', 'sumber', 'status_validasi', 'catatan_revisi',
    ];

    protected $casts = ['masa_berlaku' => 'date'];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function pts()
    {
        return $this->belongsTo(Pts::class);
    }
}
