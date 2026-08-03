<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JumlahPts extends Model
{
    const UPDATED_AT = null;

    protected $table = 'jumlah_pts';
    protected $fillable = ['tahun_anggaran_id', 'jumlah'];

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }
}
