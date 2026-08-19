<?php

namespace App\Models;

use App\Models\Concerns\HasStatusPengiriman;
use Illuminate\Database\Eloquent\Model;

class UsulanProgramKerja extends Model
{
    use HasStatusPengiriman;

    protected $table = 'usulan_program_kerja';
    protected $fillable = [
        'iku_id', 'tahun_anggaran_id', 'tahun',
        'nama_kegiatan', 'permasalahan', 'latar_belakang',
        'file_kak_pdf', 'file_rab_pdf', 'file_rab_excel',
        'status', 'catatan_revisi',
    ];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    /** Rule: satu Program Kerja Utama hanya boleh punya satu Detail Kegiatan. */
    public function detailKegiatan()
    {
        return $this->hasOne(DetailKegiatan::class, 'program_kerja_id');
    }

    /**
     * Tombol Kirim aktif jika 3 file lengkap DAN Detail Kegiatan sudah diisi.
     * Detail Kegiatan tidak lagi punya alur kirim sendiri — statusnya
     * mengikuti status Usulan Program Kerja induk (lihat DetailKegiatan).
     */
    public function getCanKirimAttribute(): bool
    {
        $filesLengkap = filled($this->file_kak_pdf) && filled($this->file_rab_pdf) && filled($this->file_rab_excel);

        return $filesLengkap && $this->detailKegiatan()->exists();
    }
}