<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JumlahPublikasi extends Model
{
    protected $table = 'jumlah_publikasi';
    protected $fillable = ['tahun_anggaran_id', 'capaian_kinerja_id', 'jumlah', 'diperbarui_oleh'];

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }

    public function diperbaruiOleh()
    {
        return $this->belongsTo(User::class, 'diperbarui_oleh');
    }
}