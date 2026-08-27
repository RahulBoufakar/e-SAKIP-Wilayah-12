<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iku extends Model
{
    protected $table = 'iku';
    protected $fillable = ['sasaran_kegiatan_id', 'kode', 'jenis','deskripsi', 
    'target_pk', 'satuan', 'deskripsi_target', 'tim_kerja_id', 'formula_kode'];

    protected static function booted(): void
    {
        // D-3: kode = "[{jenis} {urutan_sasaran}.{urutan_iku}]", mis. "[iku 1.1]"
        static::creating(function (Iku $iku) {
            $sasaran = SasaranKegiatan::findOrFail($iku->sasaran_kegiatan_id);
            $nomorSasaran = (int) str_replace('s.', '', $sasaran->kode);
            $urutan = static::where('sasaran_kegiatan_id', $iku->sasaran_kegiatan_id)->count() + 1;
            $iku->kode = "[" . strtolower($iku->jenis) . " {$nomorSasaran}.{$urutan}]";
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
        return $this->belongsTo(TimKerja::class);
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
}
