<?php

namespace App\Http\Controllers\Pimpinan\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;

class KalenderProkerController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use GatesUsulanProgramKerja;

    private const BULAN_INDO = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /pimpinan/kalender-proker?tahun=berjalan|h_plus_1 — baca-saja, lintas semua Tim Kerja,
    // hanya proker yang sudah tervalidasi (approved) — Pimpinan tidak perlu melihat draft internal.
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('pimpinan.layout.app', 'pimpinan.dashboard');
        }

        $activeTahun = (int) TahunAnggaran::find($tahunAnggaranId)->tahun;
        $nextYear = $activeTahun + 1;
        $nextYearAvailable = $this->nextTahunAnggaranExists($tahunAnggaranId);

        $tab = $request->get('tahun') === 'h_plus_1' && $nextYearAvailable ? 'h_plus_1' : 'berjalan';
        $tahun = $tab === 'h_plus_1' ? $nextYear : $activeTahun;

        $prokerList = UsulanProgramKerja::with(['iku.timKerja', 'detailKegiatan'])
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahun)
            ->whereHas('detailKegiatan')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $semuaProkerFilter = UsulanProgramKerja::with('detailKegiatan')
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahun)
            ->whereHas('detailKegiatan')
            ->get();

        $prokerPerIkuBulan = $semuaProkerFilter
            ->groupBy('iku_id')
            ->map(function ($prokerIku) {
                return collect(range(1, 12))->mapWithKeys(function ($b) use ($prokerIku) {
                    $items = $prokerIku
                        ->filter(fn ($p) => in_array($b, $p->detailKegiatan->bulan_kegiatan ?? []))
                        ->map(fn ($p) => ['id' => $p->id, 'nama' => $p->nama_usulan])
                        ->values();

                    return [$b => $items];
                });
            });

        $bulanIndo = self::BULAN_INDO;

        return view('pimpinan.program-kerja.kalender-proker.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable', 'bulanIndo', 'prokerPerIkuBulan'
        ));
    }
}
