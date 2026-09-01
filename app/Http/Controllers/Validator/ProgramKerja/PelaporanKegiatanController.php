<?php

namespace App\Http\Controllers\Validator\ProgramKerja;

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\DokumenLaporanKegiatan;
use App\Models\LaporanKegiatan;
use App\Models\ProgramKerja;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PelaporanKegiatanController extends Controller
{
    use ResolvesActiveTahunAnggaran;
    use GatesUsulanProgramKerja;

    // GET /validator/pelaporan-kegiatan?tahun=berjalan|h_plus_1 — semua Tim Kerja, tidak difilter
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

        $prokerList = ProgramKerja::with(['usulanProgramKerja.iku.timKerja', 'laporanKegiatan.dokumen'])
            ->whereHas('usulanProgramKerja', fn ($q) => $q->where('tahun', $tahun))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('validator.program-kerja.pelaporan-kegiatan.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable'
        ));
    }

    // GET /validator/pelaporan-kegiatan/{programKerja}
    public function show(Request $request, ProgramKerja $programKerja)
    {
        $programKerja->load('usulanProgramKerja.iku.timKerja');

        // firstOrCreate juga di sisi Validator, supaya tombol Kunci selalu tersedia
        // walau Tim Kerja belum pernah membuka halaman detail laporan ini.
        $laporan = LaporanKegiatan::firstOrCreate(['proker_id' => $programKerja->id]);
        $laporan->load('dokumen');

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $activeTahun = $tahunAnggaranId ? (int) TahunAnggaran::find($tahunAnggaranId)->tahun : null;
        $tab = $activeTahun !== null && $programKerja->usulanProgramKerja->tahun == $activeTahun ? 'berjalan' : 'h_plus_1';

        return view('validator.program-kerja.pelaporan-kegiatan.show', compact('programKerja', 'laporan', 'tab'));
    }

    // PUT /validator/pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/validasi
    public function validasi(Request $request, DokumenLaporanKegiatan $dokumenLaporanKegiatan)
    {
        if (Auth::user()->cannot('validasi', $dokumenLaporanKegiatan)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Dokumen ini tidak dapat divalidasi (belum ada file, atau laporan sudah dikunci).']);
        }

        $validator = Validator::make($request->all(), [
            'status_validasi' => ['required', Rule::in(['menunggu_validasi', 'disetujui', 'ditolak'])],
            'catatan_revisi' => 'required_if:status_validasi,ditolak|nullable|string',
        ], [
            'status_validasi.required' => 'Status validasi wajib dipilih.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi saat menolak dokumen.',
        ]);

        if ($validator->fails()) {
            return back()->with('feedback', ['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $data = $validator->validated();

        $dokumenLaporanKegiatan->update([
            'status_validasi' => $data['status_validasi'],
            'catatan_revisi' => $data['status_validasi'] === 'ditolak' ? $data['catatan_revisi'] : null,
        ]);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Status validasi dokumen berhasil disimpan.']);
    }

    // PUT /validator/pelaporan-kegiatan/{laporanKegiatan}/toggle-kunci
    public function toggleKunci(LaporanKegiatan $laporanKegiatan)
    {
        if (Auth::user()->cannot('toggleLock', $laporanKegiatan)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Anda tidak memiliki izin untuk mengubah status kunci laporan ini.']);
        }

        $laporanKegiatan->update(['is_locked' => ! $laporanKegiatan->is_locked]);

        return back()->with('feedback', [
            'type' => 'success',
            'message' => $laporanKegiatan->is_locked ? 'Laporan berhasil dikunci.' : 'Kunci laporan berhasil dibuka.',
        ]);
    }
}
