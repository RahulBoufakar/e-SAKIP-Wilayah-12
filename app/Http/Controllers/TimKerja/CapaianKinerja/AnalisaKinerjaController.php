<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;

class AnalisaKinerjaController extends Controller
{
    use ResolvesTimKerjaSession;

    // GET /tim-kerja/analisa-kinerja?triwulan=TW1..TW4
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

        $triwulanList = Triwulan::orderBy('urutan')->get();

        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $triwulanDipilih = $triwulanList->first(fn ($tw) => $tw->kode === strtoupper((string) $request->get('triwulan')))
            ?? $triwulanList->first(fn ($tw) => $triwulanAktifStatus && $tw->id === $triwulanAktifStatus->triwulan_id)
            ?? $triwulanList->first();

        $isTriwulanAktif = $triwulanDipilih && $triwulanAktifStatus && $triwulanDipilih->id === $triwulanAktifStatus->triwulan_id;

        $ikuList = collect();

        if ($triwulanDipilih) {
            $ikuList = Iku::with(['analisaKinerja' => fn ($q) => $q->where('triwulan_id', $triwulanDipilih->id)
                    ->where('tahun_anggaran_id', $tahunAnggaranId)])
                ->whereIn('tim_kerja_id', $timKerjaIds)
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get();
        }
        // dd($ikuList);

        return view('tim-kerja.capaian-kinerja.analisa-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
    }

    // PUT /tim-kerja/analisa-kinerja/{iku}/{triwulan}
    public function storeOrUpdate(Request $request, Iku $iku, Triwulan $triwulan)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        abort_unless($this->activeTimKerjaIds()->contains($iku->tim_kerja_id), 403);

        // Rule sama seperti Capaian Kinerja: hanya boleh isi/revisi di Triwulan aktif.
        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();
        if (! $triwulanAktifStatus || $triwulanAktifStatus->triwulan_id !== $triwulan->id) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Analisis Kinerja hanya dapat diisi/direvisi pada Triwulan yang sedang aktif.']);
        }

        $analisa = AnalisaKinerja::firstOrNew([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulan->id,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);

        if ($analisa->exists && $analisa->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat diubah.']);
        }

        $data = $request->validate([
            'progress' => 'required|string|max:255',
            'kendala' => 'required|string',
            'tindak_lanjut' => 'required|string',
        ], [
            'progress.required' => 'Progress wajib diisi.',
            'kendala.required' => 'Kendala wajib diisi.',
            'tindak_lanjut.required' => 'Tindak lanjut wajib diisi.',
        ]);

        // Sesuai keputusan: sekali Simpan langsung dikirim untuk validasi (tanpa tombol Kirim terpisah).
        $data['status'] = 'menunggu_validasi';
        $data['catatan_revisi'] = null;

        $analisa->simpan($data);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Analisis Kinerja berhasil dikirim untuk validasi.']);
    }
}