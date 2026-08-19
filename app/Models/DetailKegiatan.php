<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetailKegiatan extends Model
{
    protected $table = 'detail_kegiatan';
    protected $fillable = [
        'program_kerja_id', 'nama_detail', 'tempat_pelaksanaan',
        'bentuk_kegiatan', 'bulan_kegiatan', 'anggaran',
    ];

    protected $casts = [
        'bulan_kegiatan' => 'array',
        'anggaran' => 'decimal:2',
    ];

    public function programKerja()
    {
        return $this->belongsTo(UsulanProgramKerja::class, 'program_kerja_id');
    }
}