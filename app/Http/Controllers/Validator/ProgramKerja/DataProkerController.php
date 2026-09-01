<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\DetailKegiatan;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataProkerController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use GatesUsulanProgramKerja;

    private const BULAN_INDO = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    // GET /validator/data-proker?tahun=berjalan|h_plus_1
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

        $prokerList = UsulanProgramKerja::with(['iku.timKerja', 'programKerja', 'detailKegiatan'])
            ->where('status_validasi', 'approved')
            ->where('tahun', $tahun)
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $bulanIndo = self::BULAN_INDO;

        return view('validator.program-kerja.data-proker.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable', 'bulanIndo'
        ));
    }

    // PUT /validator/data-proker/detail-kegiatan/{detailKegiatan}/jenis-kegiatan
    public function updateJenisKegiatan(Request $request, DetailKegiatan $detailKegiatan)
    {
        if (Auth::user()->cannot('updateJenisKegiatan', $detailKegiatan)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Anda tidak memiliki izin untuk memvalidasi Jenis Kegiatan ini.']);
        }

        $data = $request->validate([
            'jenis_kegiatan' => 'nullable|in:kunjungan_lapangan,lainnya',
        ], [
            'jenis_kegiatan.in' => 'Jenis Kegiatan tidak valid.',
        ]);

        $detailKegiatan->update(['jenis_kegiatan' => $data['jenis_kegiatan'] ?? null]);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Jenis Kegiatan berhasil diperbarui.']);
    }
}
