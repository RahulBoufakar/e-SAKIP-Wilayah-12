<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;

class KalenderProkerController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    private const BULAN_INDO = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /tim-kerja/kalender-proker?tahun=berjalan|h_plus_1&tampilkan_semua=1
    // Default: hanya usulan berstatus approved. tampilkan_semua=1 menambahkan
    // status menunggu_validasi (ditandai badge "Belum Tervalidasi" di view).
    // Hak akses: hanya proker milik Tim Kerja user yang sedang login.
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        $timKerjaIds = $this->activeTimKerjaIds();
        if ($timKerjaIds->isEmpty()) {
            return view('admin.layout.app-error-content', [
                'errorMessage' => 'Anda belum ditugaskan ke Tim Kerja manapun. Hubungi Administrator.',
                'layout' => 'tim-kerja.layout.app',
                'backRoute' => 'tim-kerja.dashboard',
            ]);
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
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->whereHas('detailKegiatan')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();

        // Agregasi per IKU per bulan untuk tooltip/modal circle kalender: dihitung
        // dari SELURUH data yang lolos filter (bukan hanya halaman pagination aktif).
        $semuaProkerFilter = UsulanProgramKerja::with('detailKegiatan')
            ->whereIn('status_validasi', $statuses)
            ->where('tahun', $tahun)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
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

        return view('tim-kerja.program-kerja.kalender-proker.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable', 'tampilkanSemua', 'bulanIndo', 'prokerPerIkuBulan'
        ));
    }
}
