<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKegiatan extends Model
{
    protected $table = 'detail_kegiatan';
    protected $fillable = [
        'usulan_program_kerja_id', 'nama_detail', 'tempat_pelaksanaan',
        'bentuk_kegiatan', 'bulan_kegiatan', 'anggaran', 'jenis_kegiatan', 'permasalahan'
    ];

    protected $casts = [
        'bulan_kegiatan' => 'array',
        'anggaran' => 'decimal:2',
    ];

    public function usulanProgramKerja()
    {
        return $this->belongsTo(UsulanProgramKerja::class);
    }
}