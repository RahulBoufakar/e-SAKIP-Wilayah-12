<?php

namespace App\Models;

use App\Models\Concerns\HasStatusPengiriman;
use Illuminate\Database\Eloquent\Model;

class PelaporanKegiatan extends Model
{
    use HasStatusPengiriman;

    protected $table = 'pelaporan_kegiatan';
    protected $fillable = ['iku_id', 'triwulan_id', 'tahun_anggaran_id', 'status', 'catatan_revisi'];

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