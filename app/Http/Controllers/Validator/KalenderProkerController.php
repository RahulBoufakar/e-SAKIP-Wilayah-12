<?php

namespace App\Http\Controllers\Validator;

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

    // GET /validator/kalender-proker?tahun=berjalan|h_plus_1&tampilkan_semua=1
    // Hak akses: seluruh proker dari semua Tim Kerja (tidak difilter).
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        $activeTahun = (int) TahunAnggaran::find($tahunAnggaranId)->tahun;
        $nextYear = $activeTahun + 1;
        $nextYearAvailable = $this->nextTahunAnggaranExists($tahunAnggaranId);

        $tab = $request->get('tahun') === 'h_plus_1' && $nextYearAvailable ? 'h_plus_1' : 'berjalan';
        $tahun = $tab === 'h_plus_1' ? $nextYear : $activeTahun;

        $tampilkanSemua = $request->boolean('tampilkan_semua');
        $statuses = $tampilkanSemua ? ['approved', 'menunggu_validasi'] : ['approved'];

        $prokerList = UsulanProgramKerja::with(['iku.timKerja', 'detailKegiatan'])
            ->whereIn('status_validasi', $statuses)
            ->where('tahun', $tahun)
            ->whereHas('detailKegiatan')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        $semuaProkerFilter = UsulanProgramKerja::with('detailKegiatan')
            ->whereIn('status_validasi', $statuses)
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

        return view('validator.kalender-proker.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable', 'tampilkanSemua', 'bulanIndo', 'prokerPerIkuBulan'
        ));
    }
}