<?php

namespace App\Models;

use App\Models\Concerns\HasRowValidation;
use Illuminate\Database\Eloquent\Model;

/**
 * Baris detail IKU 7 (Dosen PTS Naik Jabatan Fungsional) — Spek §4.3 tabel 8.
 * Realisasi = COUNT(DISTINCT nidn) baris disetujui (§5 Pola 5) — satuan
 * Orang, TANPA pembagian/persentase. Bukti dukung wajib (SK Kenaikan Jafung).
 */
class CapaianDosenNaikJafung extends Model
{
    use HasRowValidation;

    protected $table = 'capaian_dosen_naik_jafung';
    protected $fillable = [
        'capaian_kinerja_id', 'pts_id', 'nama_dosen', 'nidn', 'tipe_kepegawaian',
        'jenjang_asal', 'jenjang_baru', 'no_sk', 'tanggal_sk',
        'file_bukti_dukung', 'status_validasi', 'catatan_revisi',
    ];

    protected $casts = ['tanggal_sk' => 'date'];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function pts()
    {
        return $this->belongsTo(Pts::class);
    }
}
