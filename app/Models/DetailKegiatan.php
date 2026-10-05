<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class DetailKegiatan extends Model
{
    protected $table = 'detail_kegiatan';
    protected $fillable = [
        'usulan_program_kerja_id', 'nama_detail', 'tempat_pelaksanaan',
        'bentuk_kegiatan', 'tanggal_mulai', 'tanggal_selesai', 'anggaran', 'jenis_kegiatan',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'anggaran' => 'decimal:2',
    ];

    /**
     * Bulan kegiatan (angka 1-12) yang tercakup rentang tanggal_mulai s/d tanggal_selesai,
     * mis. 3 Mar - 15 Mei => [3, 4, 5]. Rentang selalu dalam satu tahun usulan (divalidasi saat simpan).
     */
    protected function bulanKegiatan(): Attribute
    {
        return Attribute::get(function () {
            if (! $this->tanggal_mulai || ! $this->tanggal_selesai
                || $this->tanggal_selesai->lt($this->tanggal_mulai)
                || $this->tanggal_mulai->year !== $this->tanggal_selesai->year) {
                return [];
            }

            return range($this->tanggal_mulai->month, $this->tanggal_selesai->month);
        });
    }

    public function usulanProgramKerja()
    {
        return $this->belongsTo(UsulanProgramKerja::class);
    }
}
