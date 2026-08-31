<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Pts;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;

class DataProkerController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    private const BULAN_INDO = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /tim-kerja/data-proker?tahun=berjalan|h_plus_1
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

        $prokerList = UsulanProgramKerja::with(['iku', 'programKerja', 'detailKegiatan', 'pts'])
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahun)
            ->whereHas('iku', fn ($q) => $q->whereIn('tim_kerja_id', $timKerjaIds))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $ptsOptions = Pts::orderBy('nama_pts')->get(['id', 'kode_pts', 'nama_pts']);

        $bulanIndo = self::BULAN_INDO;

        return view('tim-kerja.program-kerja.data-proker.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable', 'bulanIndo', 'ptsOptions'
        ));
    }
}
