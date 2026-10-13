<?php

namespace App\Http\Controllers\Concerns;

use App\Models\CapaianKinerja;
use App\Models\Iku;
use Illuminate\Http\Request;

/** Dipakai controller Tim Kerja\CapaianKinerja\*; butuh ResolvesTimKerjaSession (activeTahunAnggaranId). */
trait ResolvesCapaianAktif
{
    /** Header capaian untuk IKU + triwulan_id di request, lalu otorisasi lewat CapaianKinerjaPolicy. */
    private function resolveCapaianAktif(Request $request, Iku $iku, string $ability = 'view'): CapaianKinerja
    {
        $capaian = CapaianKinerja::where('iku_id', $iku->id)
            ->where('tahun_anggaran_id', $this->activeTahunAnggaranId($request))
            ->where('triwulan_id', (int) $request->input('triwulan_id', $request->query('triwulan_id')))
            ->firstOrFail();
        $capaian->setRelation('iku', $iku);

        $this->authorize($ability, $capaian);

        return $capaian;
    }

    private function configKomponen(string $tipeIku, string $komponen): array
    {
        $config = CapaianKinerja::configKomponen($tipeIku, $komponen);

        abort_unless($config, 404);

        return $config;
    }
}
