<?php

namespace App\Http\Controllers\TimKerja;

use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\PelaporanKegiatan;
use App\Models\TahunAnggaran;
use App\Models\TriwulanStatus;
use App\Models\UsulanProgramKerja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class DashboardController extends Controller
{
    use ResolvesTimKerjaSession;

    private const STATUS_LIST = ['draft', 'menunggu_validasi', 'approved', 'rejected'];

    private const MODUL = [
        PelaporanKegiatan::class => ['label' => 'Pelaporan Kegiatan', 'route' => 'tim-kerja.pelaporan-kegiatan.index'],
        CapaianKinerja::class => ['label' => 'Capaian Kinerja', 'route' => 'tim-kerja.capaian-kinerja.index'],
        AnalisaKinerja::class => ['label' => 'Analisa Kinerja', 'route' => 'tim-kerja.analisa-kinerja.index'],
    ];

    // GET /tim-kerja/dashboard
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

        $ikuIds = Iku::whereIn('tim_kerja_id', $timKerjaIds)
            ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->pluck('id');

        $tahunAktif = TahunAnggaran::find($tahunAnggaranId)?->tahun;

        // (1) Breakdown status Usulan Program Kerja untuk tahun anggaran aktif
        $statusCounts = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $tahunAktif)
            ->selectRaw('status_validasi, count(*) as total')
            ->groupBy('status_validasi')
            ->pluck('total', 'status_validasi');

        $usulanStatusBreakdown = collect(self::STATUS_LIST)
            ->mapWithKeys(fn ($status) => [$status => (int) ($statusCounts[$status] ?? 0)]);
        $jumlahUsulan = $usulanStatusBreakdown->sum();

        $usulanPerIku = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $tahunAktif)
            ->with('iku:id,kode,deskripsi')
            ->select('iku_id')
            ->selectRaw('count(*) as total')
            ->groupBy('iku_id')
            ->get();

        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $rataCapaian = null;
        $kelengkapanRealisasi = null;
        $ikuCapaianChart = collect();

         if ($triwulanAktif) {
            $realisasiList = CapaianKinerja::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->with('iku:id,kode')
                ->get();

            $capaianValues = $realisasiList->map(fn ($r) => $r->capaian)->filter(fn ($c) => $c !== null);
            $rataCapaian = $capaianValues->isNotEmpty() ? round($capaianValues->avg(), 2) : null;

            $terisi = $realisasiList->whereNotNull('realisasi')->count();
            $kelengkapanRealisasi = [
                'total' => $ikuIds->count(),
                'terisi' => $terisi,
                'persen' => $ikuIds->count() > 0 ? round($terisi / $ikuIds->count() * 100) : 0,
            ];

            $ikuCapaianChart = $realisasiList->map(fn ($r) => [
                'kode' => $r->iku->kode ?? '-',
                'target' => (float) ($r->target ?? 0),
                'realisasi' => (float) ($r->realisasi ?? 0),
            ]);
        }

        // (3) Daftar item ditolak lintas modul
        $itemDitolak = collect();

        $usulanDitolak = UsulanProgramKerja::whereIn('iku_id', $ikuIds)
            ->where('tahun', $tahunAktif)
            ->where('status_validasi', 'rejected')
            ->with('iku:id,kode,deskripsi')
            ->get();

        foreach ($usulanDitolak as $row) {
            $itemDitolak->push([
                'modul' => 'Usulan Program Kerja',
                'iku_kode' => $row->iku->kode ?? '-',
                'iku_deskripsi' => $row->iku->deskripsi ?? '-',
                'triwulan' => 'Tahun '.$row->tahun,
                'catatan_revisi' => $row->catatan_revisi,
                'updated_at' => $row->updated_at,
                'url' => route('tim-kerja.usulan-program-kerja.show', $row->id),
            ]);
        }

        foreach (self::MODUL as $modelClass => $meta) {
            $rows = $modelClass::whereIn('iku_id', $ikuIds)
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('status', 'ditolak')
                ->with(['iku:id,kode,deskripsi', 'triwulan:id,kode'])
                ->get();

            foreach ($rows as $row) {
                $itemDitolak->push([
                    'modul' => $meta['label'],
                    'iku_kode' => $row->iku->kode ?? '-',
                    'iku_deskripsi' => $row->iku->deskripsi ?? '-',
                    'triwulan' => $row->triwulan->kode ?? '-',
                    'catatan_revisi' => $row->catatan_revisi,
                    'updated_at' => $row->updated_at,
                    'url' => Route::has($meta['route']) ? route($meta['route'], ['iku' => $row->iku_id]) : null,
                ]);
            }
        }
        $itemDitolak = $itemDitolak->sortByDesc('updated_at')->take(10)->values();

        return view('tim-kerja.dashboard.index', compact(
            'jumlahUsulan', 'usulanStatusBreakdown', 'usulanPerIku', 'triwulanAktif',
            'rataCapaian', 'kelengkapanRealisasi', 'ikuCapaianChart', 'itemDitolak'
        ));
    }
}