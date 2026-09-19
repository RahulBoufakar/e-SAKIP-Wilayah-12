<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SasaranKegiatan extends Model
{
    protected $table = 'sasaran_kegiatan';
    protected $fillable = ['tahun_anggaran_id', 'kode', 'nama_sasaran'];

    protected static function booted(): void
    {
        // FR-03 / D-2: auto-generate kode "s.N", reset per tahun_anggaran_id
        // AUDIT § A5.2: kunci baris TahunAnggaran (scope owner penomoran)
        // supaya dua Sasaran Kegiatan yang dibuat nyaris bersamaan pada tahun
        // anggaran yang sama tidak mendapat kode yang sama.
        static::creating(function (SasaranKegiatan $sasaran) {
            DB::transaction(function () use ($sasaran) {
                TahunAnggaran::whereKey($sasaran->tahun_anggaran_id)->lockForUpdate()->firstOrFail();

                $lastKode = static::where('tahun_anggaran_id', $sasaran->tahun_anggaran_id)
                    ->orderByDesc('id')
                    ->value('kode');
                $next = $lastKode ? ((int) str_replace('s.', '', $lastKode)) + 1 : 1;
                $sasaran->kode = "s.{$next}";
            });
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
