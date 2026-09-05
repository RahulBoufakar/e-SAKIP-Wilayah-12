<?php

namespace App\Http\Controllers\TimKerja\ProgramKerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\DokumenLaporanKegiatan;
use App\Models\LaporanKegiatan;
use App\Models\ProgramKerja;
use App\Models\TahunAnggaran;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PelaporanKegiatanController extends Controller
{
    use ResolvesTimKerjaSession;
    use GatesUsulanProgramKerja;

    // GET /tim-kerja/pelaporan-kegiatan?tahun=berjalan|h_plus_1
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

        $prokerList = ProgramKerja::with(['usulanProgramKerja.iku.timKerja', 'laporanKegiatan.dokumen'])
            ->whereHas('usulanProgramKerja', function ($q) use ($timKerjaIds, $tahun) {
                $q->where('tahun', $tahun)
                    ->whereHas('iku', fn ($qi) => $qi->whereIn('tim_kerja_id', $timKerjaIds));
            })
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('tim-kerja.program-kerja.pelaporan-kegiatan.index', compact(
            'prokerList', 'tab', 'tahun', 'activeTahun', 'nextYear', 'nextYearAvailable'
        ));
    }

    // GET /tim-kerja/pelaporan-kegiatan/{programKerja}
    public function show(Request $request, ProgramKerja $programKerja)
    {
        $this->authorize('view', $programKerja);

        $laporan = LaporanKegiatan::firstOrCreate(['proker_id' => $programKerja->id]);
        $laporan->load('dokumen');

        $programKerja->load('usulanProgramKerja.iku.timKerja');

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $activeTahun = $tahunAnggaranId ? (int) TahunAnggaran::find($tahunAnggaranId)->tahun : null;
        $tab = $activeTahun !== null && $programKerja->usulanProgramKerja->tahun == $activeTahun ? 'berjalan' : 'h_plus_1';

        $dokumenKustomExisting = $laporan->dokumen
            ->whereNotIn('nama_dokumen', DokumenLaporanKegiatan::DOKUMEN_STANDAR)
            ->values();

        return view('tim-kerja.program-kerja.pelaporan-kegiatan.show', [
            'programKerja' => $programKerja,
            'laporan' => $laporan,
            'dokumenStandar' => DokumenLaporanKegiatan::DOKUMEN_STANDAR,
            'dokumenKustomExisting' => $dokumenKustomExisting,
            'tab' => $tab,
        ]);
    }

    // POST /tim-kerja/pelaporan-kegiatan/{laporanKegiatan}/dokumen
    public function storeDokumen(Request $request, LaporanKegiatan $laporanKegiatan)
    {
        $this->authorizeAksesProker($laporanKegiatan->proker);

        abort_if($laporanKegiatan->is_locked, 403, 'Laporan ini sudah dikunci oleh Validator dan tidak dapat diubah.');

        $data = $request->validate([
            'dokumen_standar' => 'nullable|array',
            'dokumen_standar.*' => Rule::in(DokumenLaporanKegiatan::DOKUMEN_STANDAR),
            'dokumen_kustom_id' => 'nullable|array',
            'dokumen_kustom_id.*' => [
                'integer',
                Rule::exists('dokumen_laporan_kegiatan', 'id')->where(fn ($q) => $q->where('laporan_id', $laporanKegiatan->id)),
            ],
            'dokumen_lainnya' => 'nullable|array',
            'dokumen_lainnya.*' => 'nullable|string|max:255',
        ]);

        $dipilihStandar = $data['dokumen_standar'] ?? [];
        $dipilihKustomId = array_map('intval', $data['dokumen_kustom_id'] ?? []);

        $existingStandar = $laporanKegiatan->dokumen()
            ->whereIn('nama_dokumen', DokumenLaporanKegiatan::DOKUMEN_STANDAR)
            ->get();

        $existingKustom = $laporanKegiatan->dokumen()
            ->whereNotIn('nama_dokumen', DokumenLaporanKegiatan::DOKUMEN_STANDAR)
            ->get();

        // Tambah dokumen standar yang baru dicentang
        foreach ($dipilihStandar as $nama) {
            if (! $existingStandar->contains('nama_dokumen', $nama)) {
                $laporanKegiatan->dokumen()->create(['nama_dokumen' => $nama]);
            }
        }

        // Hapus dokumen standar yang di-uncheck. Dokumen berstatus 'disetujui' TIDAK
        // PERNAH dihapus apa pun isi request-nya — proteksi terhadap manipulasi
        // client-side/DevTools pada checkbox yang seharusnya ter-disable.
        foreach ($existingStandar as $dok) {
            if ($dok->status_validasi === 'disetujui') {
                continue;
            }
            if (! in_array($dok->nama_dokumen, $dipilihStandar, true)) {
                $this->hapusDokumen($dok);
            }
        }

        // Hapus dokumen kustom yang di-uncheck, dengan proteksi yang sama
        foreach ($existingKustom as $dok) {
            if ($dok->status_validasi === 'disetujui') {
                continue;
            }
            if (! in_array($dok->id, $dipilihKustomId, true)) {
                $this->hapusDokumen($dok);
            }
        }

        // Tambah dokumen kustom baru
        foreach (array_filter($data['dokumen_lainnya'] ?? []) as $nama) {
            $laporanKegiatan->dokumen()->create(['nama_dokumen' => $nama]);
        }

        event(new ActivityOccurred(
            subject: $laporanKegiatan,
            description: "memperbarui daftar dokumen pada laporan kegiatan {$laporanKegiatan->proker->kode_proker}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Dokumen berhasil diperbarui.']);
    }

    // PUT /tim-kerja/pelaporan-kegiatan/dokumen/{dokumenLaporanKegiatan}/upload
    public function uploadDokumen(Request $request, DokumenLaporanKegiatan $dokumenLaporanKegiatan)
    {
        $this->authorizeAksesProker($dokumenLaporanKegiatan->laporan->proker);

        abort_if($dokumenLaporanKegiatan->laporan->is_locked, 403, 'Laporan ini sudah dikunci oleh Validator dan tidak dapat diubah.');
        abort_if($dokumenLaporanKegiatan->isLocked(), 403, 'Dokumen ini sudah disetujui dan tidak dapat diubah.');

        $validator = Validator::make($request->all(), [
            'file_dokumen' => 'required|file|mimes:pdf|max:5120',
        ], [
            'file_dokumen.required' => 'File dokumen wajib diunggah.',
            'file_dokumen.mimes' => 'File dokumen harus berformat PDF.',
            'file_dokumen.max' => 'Ukuran file maksimal 5 MB.',
        ]);

        if ($validator->fails()) {
            return back()->with('feedback', ['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        if ($dokumenLaporanKegiatan->file_dokumen) {
            Storage::disk('public')->delete($dokumenLaporanKegiatan->file_dokumen);
        }

        $dokumenLaporanKegiatan->update([
            'file_dokumen' => $request->file('file_dokumen')->store('laporan-kegiatan', 'public'),
            'status_validasi' => 'menunggu_validasi',
            'catatan_revisi' => null,
        ]);

        $kodeProker = $dokumenLaporanKegiatan->laporan->proker->kode_proker ?? '-';

        event(new ActivityOccurred(
            subject: $dokumenLaporanKegiatan,
            description: "mengunggah dokumen \"{$dokumenLaporanKegiatan->nama_dokumen}\" pada laporan kegiatan {$kodeProker}",
            causer: Auth::user(),
            recipients: User::role('validator')->get(),
            url: route('validator.pelaporan-kegiatan.show', $dokumenLaporanKegiatan->laporan->proker_id),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Dokumen berhasil diunggah.']);

    }

    private function hapusDokumen(DokumenLaporanKegiatan $dokumen): void
    {
        if ($dokumen->file_dokumen) {
            Storage::disk('public')->delete($dokumen->file_dokumen);
        }
        $dokumen->delete();
    }

    private function authorizeAksesProker(ProgramKerja $programKerja): void
    {
        abort_unless(
            $this->activeTimKerjaIds()->contains($programKerja->usulanProgramKerja->iku->tim_kerja_id),
            403
        );
    }
}
