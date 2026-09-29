<?php

namespace App\Http\Controllers\Validator\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Services\CapaianKinerjaHitungService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Refaktor Capaian Kinerja Hybrid — sisi Validator. Cermin struktur
 * TimKerja\CapaianKinerja\CapaianKinerjaController (index ringkasan 9 IKU,
 * show detail per tipe_iku), ditambah aksi validasi per-baris dan bulk
 * "Setujui Semua" (Spek §7).
 */
class CapaianKinerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    private const FILE_RULE = 'file|mimes:pdf|mimetypes:application/pdf|max:5120'; // PDF, maks 5 MB
    private const FIELD_FILE = ['file_bukti_dukung', 'file_implementasi_ppks', 'file_implementasi_anti_narkoba', 'file_implementasi_anti_korupsi'];

    public function __construct(private CapaianKinerjaHitungService $hitungService)
    {
    }

    // GET /validator/capaian-kinerja — ringkasan 9 IKU, seluruh Tim Kerja, tab TW1-4
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        [$triwulanList, $triwulanDipilih, $isTriwulanAktif] = $this->resolveTabTriwulan($request, $tahunAnggaranId);

        $ikuList = collect();
        if ($triwulanDipilih) {
            $ikuList = Iku::whereNotNull('tipe_iku')
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get()
                ->map(fn (Iku $iku) => $this->tempelkanCapaianAktif($iku, $triwulanDipilih->id, $tahunAnggaranId));
        }

        return view('validator.capaian-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
    }

    // GET /validator/capaian-kinerja/{iku} — detail baris utk divalidasi, tab TW1-4
    public function show(Request $request, Iku $iku)
    {
        abort_unless($iku->tipe_iku, 404);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
        }

        [$triwulanList, $triwulanDipilih, $isTriwulanAktif] = $this->resolveTabTriwulan($request, $tahunAnggaranId);

        $capaian = CapaianKinerja::firstOrCreate([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulanDipilih->id,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);
        $capaian->setRelation('iku', $iku);

        $config = config("capaian_kinerja_tipe.{$iku->tipe_iku}");

        $view = $iku->tipe_iku === 'arsitektur_pts'
            ? 'validator.capaian-kinerja.tipe.arsitektur-pts'
            : 'validator.capaian-kinerja.tipe.generik';

        return view($view, compact('iku', 'capaian', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif', 'config'));
    }

    // PUT /validator/capaian-kinerja/{iku}/baris/{komponen}/{barisId}/validasi
    public function validasiBaris(Request $request, Iku $iku, string $komponen, int $barisId)
    {
        $capaian = $this->resolveCapaian($request, $iku);
        $baris = $capaian->relasi($komponen)->getQuery()->findOrFail($barisId);

        $data = $request->validate([
            'status_validasi' => 'required|in:disetujui,ditolak',
            'catatan_revisi' => 'required_if:status_validasi,ditolak|nullable|string',
        ], [
            'status_validasi.required' => 'Status validasi wajib dipilih.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi saat menolak.',
        ]);

        try {
            $data['status_validasi'] === 'disetujui' ? $baris->setujui() : $baris->tolak($data['catatan_revisi']);
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        $this->sinkronisasiHeader($capaian);

        $verb = $data['status_validasi'] === 'disetujui' ? 'menyetujui' : 'menolak';
        event(new ActivityOccurred(
            subject: $baris,
            description: "{$verb} data pada IKU {$iku->kode} — {$capaian->triwulan->kode}",
            causer: Auth::user(),
            recipients: $iku->timKerja?->flatMap(fn ($tim) => $tim->users)->unique('id') ?? collect(),
            url: route('tim-kerja.capaian-kinerja.show', $iku->id),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Status validasi berhasil disimpan.']);
    }

    // PUT /validator/capaian-kinerja/{iku}/setujui-semua — bulk approve seluruh baris menunggu_validasi pada TW yang sedang dilihat
    public function setujuiSemua(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaian($request, $iku);

        $jumlahDisetujui = 0;
        foreach (array_keys($iku->komponenCapaian()) as $komponen) {
            $barisMenunggu = $capaian->relasi($komponen)->getQuery()->menungguValidasi()->get();
            foreach ($barisMenunggu as $baris) {
                $baris->setujui();
                $jumlahDisetujui++;
            }
        }

        if ($jumlahDisetujui === 0) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tidak ada data yang menunggu validasi pada Triwulan ini.']);
        }

        $this->sinkronisasiHeader($capaian);

        event(new ActivityOccurred(
            subject: $capaian,
            description: "menyetujui {$jumlahDisetujui} data sekaligus pada IKU {$iku->kode} — {$capaian->triwulan->kode}",
            causer: Auth::user(),
            recipients: $iku->timKerja?->flatMap(fn ($tim) => $tim->users)->unique('id') ?? collect(),
            url: route('tim-kerja.capaian-kinerja.show', $iku->id),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => "{$jumlahDisetujui} data berhasil disetujui."]);
    }

    public function previewBukti(Request $request, Iku $iku, string $komponen, int $barisId): StreamedResponse
    {
        return $this->streamBukti($request, $iku, $komponen, $barisId, download: false);
    }

    public function unduhBukti(Request $request, Iku $iku, string $komponen, int $barisId): StreamedResponse
    {
        return $this->streamBukti($request, $iku, $komponen, $barisId, download: true);
    }

    private function streamBukti(Request $request, Iku $iku, string $komponen, int $barisId, bool $download): StreamedResponse
    {
        $capaian = $this->resolveCapaian($request, $iku);
        $baris = $capaian->relasi($komponen)->getQuery()->findOrFail($barisId);

        abort_unless($baris->file_bukti_dukung && Storage::disk('private')->exists($baris->file_bukti_dukung), 404);

        if ($download) {
            return Storage::disk('private')->download($baris->file_bukti_dukung);
        }

        return response()->stream(function () use ($baris) {
            fpassthru(Storage::disk('private')->readStream($baris->file_bukti_dukung));
        }, 200, ['Content-Type' => 'application/pdf']);
    }

    /** @return array{0: \Illuminate\Support\Collection, 1: ?Triwulan, 2: bool} */
    private function resolveTabTriwulan(Request $request, int $tahunAnggaranId): array
    {
        $triwulanList = Triwulan::orderBy('urutan')->get();
        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $tahunAnggaranId)->where('status', 'aktif')->first();

        $triwulanDipilih = $triwulanList->first(fn ($tw) => $tw->kode === strtoupper((string) $request->get('triwulan')))
            ?? $triwulanList->first(fn ($tw) => $triwulanAktifStatus && $tw->id === $triwulanAktifStatus->triwulan_id)
            ?? $triwulanList->first();

        $isTriwulanAktif = $triwulanDipilih && $triwulanAktifStatus && $triwulanDipilih->id === $triwulanAktifStatus->triwulan_id;

        return [$triwulanList, $triwulanDipilih, $isTriwulanAktif];
    }

    private function tempelkanCapaianAktif(Iku $iku, int $triwulanId, int $tahunAnggaranId): Iku
    {
        $capaian = CapaianKinerja::firstOrCreate([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulanId,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);
        $capaian->setRelation('iku', $iku);
        $iku->setRelation('capaianAktif', $capaian);

        return $iku;
    }

    private function resolveCapaian(Request $request, Iku $iku): CapaianKinerja
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $triwulanId = (int) $request->input('triwulan_id', $request->query('triwulan_id'));

        $capaian = CapaianKinerja::where('iku_id', $iku->id)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('triwulan_id', $triwulanId)
            ->firstOrFail();
        $capaian->setRelation('iku', $iku);

        return $capaian;
    }

    /** Rekalkulasi realisasi_otomatis (jika belum di-override Tim Kerja) + sinkron status agregat. */
    private function sinkronisasiHeader(CapaianKinerja $capaian): void
    {
        if (! $capaian->realisasi_override) {
            $capaian->realisasi_otomatis = $this->hitungService->hitung($capaian);
            $capaian->realisasi = $capaian->realisasi_otomatis;
        }

        $capaian->syncStatusFromBaris();
    }
    
    public function previewSetujui(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaian($request, $iku);

        return response()->json($this->hitungService->ringkasan($capaian, ['menunggu_validasi', 'disetujui']));
    }
}
