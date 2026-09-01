<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\TahunAnggaran;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

class UsulanProgramKerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use GatesUsulanProgramKerja;

    private const STATUS_VALID = ['menunggu_validasi', 'approved', 'rejected'];

    // GET /validator/usulan-program-kerja?tahun=berjalan|h_plus_1&status=menunggu_validasi
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

        $status = in_array($request->get('status'), self::STATUS_VALID, true)
            ? $request->get('status')
            : 'menunggu_validasi';

        $usulanList = UsulanProgramKerja::with(['iku.timKerja'])
            ->where('status_validasi', $status)
            ->where('tahun', $tahun)
            ->orderByDesc('tgl_validasi')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('validator.program-kerja.usulan-program-kerja.index', compact(
            'usulanList', 'status', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable'
        ));
    }

    // GET /validator/usulan-program-kerja/{usulanProgramKerja}
    public function show(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        $usulanProgramKerja->load(['iku.timKerja', 'detailKegiatan', 'validator']);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $activeTahun = $tahunAnggaranId ? (int) TahunAnggaran::find($tahunAnggaranId)->tahun : null;
        $tab = $activeTahun !== null && $usulanProgramKerja->tahun == $activeTahun ? 'berjalan' : 'h_plus_1';

        $bulanIndo = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

        return view('validator.program-kerja.usulan-program-kerja.show', [
            'usulan' => $usulanProgramKerja,
            'bulanIndo' => $bulanIndo,
            'tab' => $tab,
        ]);
    }

    // PUT /validator/usulan-program-kerja/{usulanProgramKerja}/setujui
    public function setujui(UsulanProgramKerja $usulanProgramKerja)
    {
        if (Auth::user()->cannot('approve', $usulanProgramKerja)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini tidak dapat disetujui pada status saat ini.']);
        }

        try {
            $usulanProgramKerja->setujui(Auth::id());
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja disetujui.']);
    }

    // PUT /validator/usulan-program-kerja/{usulanProgramKerja}/tolak
    public function tolak(Request $request, UsulanProgramKerja $usulanProgramKerja)
    {
        if (Auth::user()->cannot('reject', $usulanProgramKerja)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Usulan ini tidak dapat ditolak pada status saat ini.']);
        }

        $data = $request->validate([
            'catatan_revisi' => 'required|string',
        ], [
            'catatan_revisi.required' => 'Catatan revisi wajib diisi saat menolak usulan.',
        ]);

        try {
            $usulanProgramKerja->tolak(Auth::id(), $data['catatan_revisi']);
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Usulan Program Kerja ditolak.']);
    }
}