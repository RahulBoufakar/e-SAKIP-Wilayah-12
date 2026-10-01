<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\AppliesIkuTimFilter;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use App\Models\Triwulan;
use App\Services\DokumentasiService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class DokumentasiController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use AppliesIkuTimFilter;

    private const PER_PAGE = 15;

    private const KATEGORI = [
        'semua' => 'Semua Dokumen',
        'usulan' => 'Usulan Proker',
        'pelaporan' => 'Pelaporan Kegiatan',
        'capaian' => 'Capaian Kinerja',
    ];

    // GET /admin/dokumentasi?kategori=semua|usulan|pelaporan|capaian&iku_id=&tim_kerja_id=&triwulan=TW1..TW4
    public function index(Request $request, DokumentasiService $service)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $tahunAnggaran = $tahunAnggaranId ? TahunAnggaran::find($tahunAnggaranId) : null;
        if (! $tahunAnggaran) {
            return $this->missingTahunAnggaran('admin.layout.app', 'admin.dashboard');
        }

        $kategori = array_key_exists($request->get('kategori'), self::KATEGORI) ? $request->get('kategori') : 'semua';

        $triwulanList = Triwulan::orderBy('urutan')->get();
        $kodeTriwulan = strtoupper((string) $request->get('triwulan'));
        $triwulan = $triwulanList->contains('kode', $kodeTriwulan) ? $kodeTriwulan : null;

        $rows = $service->rows($kategori, $tahunAnggaran, $request, $triwulan);

        $page = LengthAwarePaginator::resolveCurrentPage();
        $dokumen = new LengthAwarePaginator(
            $rows->forPage($page, self::PER_PAGE)->values(),
            $rows->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $kategoriList = self::KATEGORI;
        $filterOptions = $this->filterOptionsTA($tahunAnggaran->id);

        return view('admin.dokumentasi.index', compact(
            'dokumen', 'kategori', 'kategoriList', 'filterOptions', 'triwulanList', 'triwulan'
        ));
    }
}