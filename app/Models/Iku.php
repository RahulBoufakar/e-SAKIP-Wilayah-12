<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Iku extends Model
{
    protected $table = 'iku';
    protected $fillable = ['sasaran_kegiatan_id', 'kode', 'jenis','deskripsi', 
    'target_pk', 'satuan', 'deskripsi_target', 'formula_kode'];

    protected static function booted(): void
    {
        // D-3: kode = "[{jenis} {urutan_sasaran}.{urutan_iku}]", mis. "[iku 1.1]"
        // AUDIT § A5.2: kunci baris SasaranKegiatan (scope owner penomoran)
        // supaya dua IKU yang dibuat nyaris bersamaan pada sasaran yang sama
        // tidak mendapat urutan/kode yang sama (race condition pada pola
        // COUNT()+1 tanpa lock).
        static::creating(function (Iku $iku) {
            DB::transaction(function () use ($iku) {
                $sasaran = SasaranKegiatan::whereKey($iku->sasaran_kegiatan_id)->lockForUpdate()->firstOrFail();
                $nomorSasaran = (int) str_replace('s.', '', $sasaran->kode);
                $urutan = static::where('sasaran_kegiatan_id', $iku->sasaran_kegiatan_id)->count() + 1;
                $iku->kode = "[" . strtolower($iku->jenis) . " {$nomorSasaran}.{$urutan}]";
            });
        });
    }

    public function sasaranKegiatan()
    {
        return $this->belongsTo(SasaranKegiatan::class);
    }

    public function rencanaAksi()
    {
        return $this->hasMany(RencanaAksi::class);
    }

    public function timKerja()
    {
        return $this->belongsToMany(TimKerja::class, 'iku_tim_kerja');
    }

    public function capaianKinerja()
    {
        return $this->hasMany(CapaianKinerja::class);
    }

    public function getNomorAttribute(): string
    {
        preg_match('/(\d+\.\d+)/', $this->kode, $matches);

        return $matches[1] ?? $this->kode;
    }

    public function analisaKinerja()
    {
        return $this->hasMany(AnalisaKinerja::class);
    }
}
