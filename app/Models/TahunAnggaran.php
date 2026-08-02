<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAnggaran extends Model
{
    const UPDATED_AT = null; // FR-25: tidak ada edit, cuma create/delete

    protected $table = 'tahun_anggaran';
    protected $fillable = ['tahun'];

    protected static function booted(): void
    {
        // D-1: begitu tahun baru dibuat, auto-seed 4 baris triwulan_status (non_aktif)
        static::created(function (TahunAnggaran $tahun) {
            Triwulan::all()->each(fn (Triwulan $tw) => TriwulanStatus::firstOrCreate([
                'tahun_anggaran_id' => $tahun->id,
                'triwulan_id' => $tw->id,
            ]));
        });
    }

    public function sasaranKegiatan()
    {
        return $this->hasMany(SasaranKegiatan::class);
    }

    public function jumlahMahasiswa()
    {
        return $this->hasMany(JumlahMahasiswa::class);
    }

    public function jumlahPts()
    {
        return $this->hasMany(JumlahPts::class);
    }

    public function triwulanStatus()
    {
        return $this->hasMany(TriwulanStatus::class);
    }
}
