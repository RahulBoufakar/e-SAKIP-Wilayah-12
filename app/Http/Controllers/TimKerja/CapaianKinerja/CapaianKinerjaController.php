<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Http\Controllers\Concerns\AppliesIkuTimFilter;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\Pts;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Services\CapaianKinerjaBerkasService;
use App\Services\CapaianKinerjaMigrasiService;
use Illuminate\Http\Request;

/**
 * Halaman Capaian Kinerja Tim Kerja (ringkasan & detail). Satu controller untuk
 * 9 tipe_iku: bentuk kolom ditangani config('capaian_kinerja_tipe') dan
 * CapaianKinerja::relasi()/komponenUntukTipe(). Aksi lain dipisah:
 * Baris (CRUD), Kirim, Migrasi, JumlahPublikasi, Bukti. Otorisasi: CapaianKinerjaPolicy.
 */
class CapaianKinerjaController extends Controller
{
    use ResolvesTimKerjaSession;
    use AppliesIkuTimFilter;

    public function __construct(
        private CapaianKinerjaMigrasiService $migrasi,
        private CapaianKinerjaBerkasService $berkas,
    ) {
    }

    // GET /tim-kerja/capaian-kinerja — ringkasan 9 IKU milik Tim Kerja, tab TW1-4
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

        [$triwulanList, $triwulanDipilih, $isTriwulanAktif] = $this->resolveTabTriwulan($request, $tahunAnggaranId);

        $ikuList = collect();
        if ($triwulanDipilih) {
            $query = Iku::whereNotNull('tipe_iku')
                ->whereHas('timKerja', fn ($q) => $q->whereIn('tim_kerja.id', $timKerjaIds))
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId));
            $this->applyIkuTimFilterOnIku($query, $request);

            $ikuList = $query->orderBy('kode')->get()
                ->map(fn (Iku $iku) => $this->tempelkanCapaianAktif($iku, $triwulanDipilih->id, $tahunAnggaranId));
        }

        $filterOptions = $this->filterOptionsTA($tahunAnggaranId, $timKerjaIds);

        return view('tim-kerja.capaian-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif', 'filterOptions'));
    }

    // GET /tim-kerja/capaian-kinerja/{iku} — detail baris per tipe_iku, tab TW1-4
    public function show(Request $request, Iku $iku)
    {
        $this->authorize('viewTimKerja', [CapaianKinerja::class, $iku]);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('tim-kerja.layout.app', 'tim-kerja.dashboard');
        }

        [$triwulanList, $triwulanDipilih, $isTriwulanAktif] = $this->resolveTabTriwulan($request, $tahunAnggaranId);

        $capaian = CapaianKinerja::firstOrCreate([
            'iku_id' => $iku->id,
            'triwulan_id' => $triwulanDipilih->id,
            'tahun_anggaran_id' => $tahunAnggaranId,
        ]);
        $capaian->setRelation('iku', $iku);

        $config = config("capaian_kinerja_tipe.{$iku->tipe_iku}");
        $ptsOptions = ($config['butuh_pts'] ?? false)
            ? Pts::orderBy('nama_pts')->get(['id', 'kode_pts', 'nama_pts'])
            : collect();

        $ringkasanMigrasi = $isTriwulanAktif ? $this->migrasi->ringkasan($capaian) : null;
        $masalahFile = $isTriwulanAktif ? $this->berkas->masalahFile($capaian) : collect();

        $view = $iku->tipe_iku === 'arsitektur_pts'
            ? 'tim-kerja.capaian-kinerja.tipe.arsitektur-pts'
            : 'tim-kerja.capaian-kinerja.tipe.generik';

        return view($view, compact('iku', 'capaian', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif', 'config', 'ptsOptions', 'ringkasanMigrasi', 'masalahFile'));
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
}
