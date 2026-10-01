<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Iku;
use App\Models\TimKerja;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

trait AppliesIkuTimFilter
{
    /**
     * Untuk query yang punya kolom iku_id (UsulanProgramKerja), atau yang
     * mengaksesnya lewat relasi $usulanRelation (mis. ProgramKerja -> 'usulanProgramKerja').
     */
    protected function applyIkuTimFilter($query, Request $request, ?string $usulanRelation = null)
    {
        $ikuId = $request->integer('iku_id');
        $timId = $request->integer('tim_kerja_id');

        if (! $ikuId && ! $timId) {
            return $query;
        }

        $scope = function ($q) use ($ikuId, $timId) {
            $q->when($ikuId, fn ($x) => $x->where('iku_id', $ikuId))
              ->when($timId, fn ($x) => $x->whereHas('iku.timKerja', fn ($t) => $t->where('tim_kerja.id', $timId)));
        };

        $usulanRelation ? $query->whereHas($usulanRelation, $scope) : $scope($query);

        return $query;
    }

    /** Untuk query yang basisnya model Iku (Capaian & Analisis Kinerja). */
    protected function applyIkuTimFilterOnIku($query, Request $request)
    {
        $ikuId = $request->integer('iku_id');
        $timId = $request->integer('tim_kerja_id');

        $query->when($ikuId, fn ($q) => $q->where('iku.id', $ikuId))
              ->when($timId, fn ($q) => $q->whereHas('timKerja', fn ($t) => $t->where('tim_kerja.id', $timId)));

        return $query;
    }

    protected function filterOptionsTahun(int $tahun, ?Collection $timKerjaIds = null): array
    {
        return $this->buildFilterOptions(
            fn ($q) => $q->whereHas('sasaranKegiatan.tahunAnggaran', fn ($t) => $t->where('tahun', $tahun)),
            $timKerjaIds
        );
    }

    protected function filterOptionsTA(int $tahunAnggaranId, ?Collection $timKerjaIds = null): array
    {
        return $this->buildFilterOptions(
            fn ($q) => $q->whereHas('sasaranKegiatan', fn ($s) => $s->where('tahun_anggaran_id', $tahunAnggaranId)),
            $timKerjaIds
        );
    }

    private function buildFilterOptions(Closure $scope, ?Collection $timKerjaIds): array
    {
        $ikuQuery = Iku::query();
        $scope($ikuQuery);

        if ($timKerjaIds) {
            $ikuQuery->whereHas('timKerja', fn ($t) => $t->whereIn('tim_kerja.id', $timKerjaIds));
        }

        return [
            'iku' => $ikuQuery->orderBy('kode')->get(['id', 'kode', 'deskripsi']),
            'tim' => TimKerja::when($timKerjaIds, fn ($q) => $q->whereIn('id', $timKerjaIds))
                ->orderBy('nama_tim')->get(['id', 'nama_tim']),
        ];
    }
}