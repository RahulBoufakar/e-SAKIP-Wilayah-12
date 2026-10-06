<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelompok extends Model
{
    public $timestamps = false;

    protected $table = 'kelompok';
    protected $fillable = ['kategori_id', 'kode_kelompok', 'nama_kelompok', 'baris_awal', 'baris_akhir', 'urutan'];

    public function kategori()
    {
        return $this->belongsTo(Kategori::class);
    }

    /** Nama kosong di master -> cadangan "Sub {kode}" (03, Q5). */
    public function getLabelAttribute(): string
    {
        return filled($this->nama_kelompok) ? $this->nama_kelompok : "Sub {$this->kode_kelompok}";
    }
}
