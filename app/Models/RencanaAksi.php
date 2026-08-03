<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RencanaAksi extends Model
{
    const CREATED_AT = null;

    protected $table = 'rencana_aksi';
    protected $fillable = ['iku_id', 'triwulan_id', 'tahun_anggaran_id', 'uraian'];

    public function iku()
    {
        return $this->belongsTo(Iku::class);
    }

    public function triwulan()
    {
        return $this->belongsTo(Triwulan::class);
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
}
