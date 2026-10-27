<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Pts extends Model
{
    protected $table = 'pts';
    protected $fillable = ['kode_pts', 'nama_pts', 'status_pts', 'akreditasi_pts', 'provinsi', 'sumber_data', 'terakhir_sinkron_at'];

    protected $casts = [
        'terakhir_sinkron_at' => 'datetime',
    ];

    public function usulanProgramKerja()
    {
        return $this->belongsToMany(UsulanProgramKerja::class, 'usulan_program_kerja_pts');
    }

    public function akreditasi(): HasOne
    {
        return $this->hasOne(PtsAkreditasi::class);
    }

    /**
     * Sembunyikan PTS berstatus tutup. Status NULL (belum ada) tetap tampil:
     * `!= 'tutup'` saja akan membuang NULL di SQL.
     */
    public function scopeTampil(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('status_pts')->orWhere('status_pts', '!=', 'tutup'));
    }

    /**
     * Opsi dropdown PTS: yang tampil, ditambah PTS (walau tutup) yang sudah dipakai
     * baris/tagging yang sedang ditampilkan, supaya tidak terlepas saat form disimpan.
     */
    public static function opsiDropdown(array $sertakanIds = []): Collection
    {
        return static::where(fn ($q) => $q->tampil()->orWhereIn('id', $sertakanIds))
            ->orderBy('nama_pts')
            ->get(['id', 'kode_pts', 'nama_pts']);
    }
}