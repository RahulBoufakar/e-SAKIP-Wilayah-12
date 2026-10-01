<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ProgramKerja extends Model
{
    protected $table = 'program_kerja';
    public const STATUS_KEGIATAN = ['Belum Dilaksanakan', 'Sedang Dilaksanakan', 'Selesai Dilaksanakan'];

    // Warna penanda status pada Kalender Proker (kelas Tailwind) + legendanya.
    public const WARNA_STATUS_KEGIATAN = [
        'Belum Dilaksanakan' => 'bg-slate-400 hover:bg-slate-500',
        'Sedang Dilaksanakan' => 'bg-blue-500 hover:bg-blue-600',
        'Selesai Dilaksanakan' => 'bg-red-500 hover:bg-red-600',
    ];

    // Latar baris program pada popup detail Kalender Proker.
    public const LATAR_STATUS_KEGIATAN = [
        'Belum Dilaksanakan' => 'border-slate-400 bg-slate-100',
        'Sedang Dilaksanakan' => 'border-blue-500 bg-blue-50',
        'Selesai Dilaksanakan' => 'border-red-500 bg-red-50',
    ];

    protected $fillable = ['usulan_program_kerja_id', 'kode_proker', 'status_kegiatan'];

    // Default model sama dengan default kolom, supaya proker yang baru dibuat langsung punya status.
    protected $attributes = [
        'status_kegiatan' => 'Belum Dilaksanakan',
    ];

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
