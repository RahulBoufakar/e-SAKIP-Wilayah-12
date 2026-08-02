<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SasaranKegiatan extends Model
{
    protected $table = 'sasaran_kegiatan';
    protected $fillable = ['tahun_anggaran_id', 'kode', 'nama_sasaran'];

    protected static function booted(): void
    {
        // FR-03 / D-2: auto-generate kode "s.N", reset per tahun_anggaran_id
        static::creating(function (SasaranKegiatan $sasaran) {
            $lastKode = static::where('tahun_anggaran_id', $sasaran->tahun_anggaran_id)
                ->orderByDesc('id')
                ->value('kode');
            $next = $lastKode ? ((int) str_replace('s.', '', $lastKode)) + 1 : 1;
            $sasaran->kode = "s.{$next}";
        });
    }

    public function tahunAnggaran()
    {
        return $this->belongsTo(TahunAnggaran::class);
    }

    public function iku()
    {
        return $this->hasMany(Iku::class);
    }
}
