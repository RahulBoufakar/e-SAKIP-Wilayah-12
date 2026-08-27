<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapaianKinerjaDokumen extends Model
{
    const UPDATED_AT = null;

    protected $table = 'capaian_kinerja_dokumen';
    protected $fillable = ['capaian_kinerja_id', 'nama_dokumen', 'file_dokumen'];

    public function capaianKinerja()
    {
        return $this->belongsTo(CapaianKinerja::class);
    }
}