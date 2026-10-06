<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kategori extends Model
{
    public $timestamps = false;

    protected $table = 'kategori';
    protected $fillable = ['sheet_id', 'kode_kategori', 'nama_kategori', 'baris_awal', 'baris_akhir', 'urutan'];

    public function sheet()
    {
        return $this->belongsTo(Sheet::class);
    }

    public function kelompok()
    {
        return $this->hasMany(Kelompok::class);
    }
}
