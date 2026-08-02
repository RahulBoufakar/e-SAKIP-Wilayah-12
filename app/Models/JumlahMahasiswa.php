<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JumlahMahasiswa extends Model
{
    const UPDATED_AT = null; // FR-25 pattern: create/delete saja, tanpa edit

    protected $table = 'jumlah_mahasiswa';
    protected $fillable = ['tahun_anggaran_id', 'jumlah'];

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
}
