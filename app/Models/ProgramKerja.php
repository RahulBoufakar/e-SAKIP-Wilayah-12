<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramKerja extends Model
{
    protected $table = 'program_kerja';
    protected $fillable = ['usulan_program_kerja_id', 'kode_proker'];

    protected static function booted(): void
    {
        // Kode Proker = "{kode_iku}.{urutan}", urutan reset per IKU per tahun.
        // Mis. IKU "2.2" -> proker pertama "2.2.1", berikutnya "2.2.2" (pola sama
        // dengan auto-generate kode pada SasaranKegiatan/Iku).
        // AUDIT § A5.2: kunci baris Iku (scope owner penomoran proker) supaya
        // dua proker yang disetujui nyaris bersamaan pada IKU+tahun yang sama
        // tidak mendapat kode_proker yang sama.
        static::creating(function (ProgramKerja $proker) {
            $usulan = UsulanProgramKerja::with('iku')->find($proker->usulan_program_kerja_id);

            if (! $usulan || ! $usulan->iku) {
                return;
            }

            DB::transaction(function () use ($proker, $usulan) {
                Iku::whereKey($usulan->iku_id)->lockForUpdate()->firstOrFail();

                preg_match('/(\d+\.\d+)/', $usulan->iku->kode, $matches);
                $kodeIku = $matches[1] ?? $usulan->iku->kode;

                $urutan = static::whereHas('usulanProgramKerja', function ($q) use ($usulan) {
                    $q->where('iku_id', $usulan->iku_id)->where('tahun', $usulan->tahun);
                })->count() + 1;

                $proker->kode_proker = "{$kodeIku}.{$urutan}";
            });
        });
    }

    public function usulanProgramKerja()
    {
        return $this->belongsTo(UsulanProgramKerja::class);
    }

    public function laporanKegiatan()
    {
        return $this->hasOne(LaporanKegiatan::class, 'proker_id');
    }
}
