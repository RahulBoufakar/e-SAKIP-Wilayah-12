<?php

namespace App\Http\Controllers\Pimpinan;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\JumlahMahasiswa;
use App\Models\JumlahPts;
use App\Models\LaporanKinerja;
use App\Models\SasaranKegiatan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    private const CACHE_TTL = 300;

    // GET /pimpinan/dashboard — ringkasan lintas semua Tim Kerja (PRD §3.1)
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('pimpinan.layout.app', 'pimpinan.dashboard');
        }

        $data = Cache::remember("pimpinan_dashboard_v1_{$tahunAnggaranId}", self::CACHE_TTL, function () use ($tahunAnggaranId) {
            return $this->buildDashboardData($tahunAnggaranId);
        });

        $laporanTerbaru = LaporanKinerja::with(['tahunAnggaran', 'triwulan'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('pimpinan.dashboard.index', array_merge($data, compact('laporanTerbaru')));
    }

    private function buildDashboardData(int $tahunAnggaranId): array
    {
        $jumlahSasaran = SasaranKegiatan::where('tahun_anggaran_id', $tahunAnggaranId)->count();

        $ikuList = Iku::whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
            ->with('timKerja')
            ->get();
        $jumlahIku = $ikuList->count();

        $triwulanAktif = TriwulanStatus::with('triwulan')
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('status', 'aktif')
            ->first();

        $rataCapaian = null;
        $ikuBermasalah = collect();

        if ($triwulanAktif) {
            $capaianAktif = CapaianKinerja::whereIn('iku_id', $ikuList->pluck('id'))
                ->where('tahun_anggaran_id', $tahunAnggaranId)
                ->where('triwulan_id', $triwulanAktif->triwulan_id)
                ->with('iku:id,kode,deskripsi,target_pk')
                ->get();

            $nilai = $capaianAktif->map(fn ($c) => $c->capaian)->filter(fn ($v) => $v !== null);
            $rataCapaian = $nilai->isNotEmpty() ? round($nilai->avg(), 2) : null;

            $ikuById = $ikuList->keyBy('id');
            $terisiIkuIds = $capaianAktif->pluck('iku_id');

            $bermasalahTerisi = $capaianAktif
                ->filter(fn ($c) => $c->status === 'ditolak' || ($c->capaian !== null && $c->capaian < 50))
                ->map(fn ($c) => [
                    'kode' => $c->iku->kode,
                    'deskripsi' => $c->iku->deskripsi,
                    'tim' => $ikuById[$c->iku_id]?->timKerja->pluck('nama_tim')->join(', ') ?: '—',
                    'masalah' => $c->status === 'ditolak' ? 'Ditolak Validator' : "Capaian Rendah ({$c->capaian}%)",
                ]);

            $belumDiisi = $ikuList->whereNotIn('id', $terisiIkuIds)
                ->map(fn ($iku) => [
                    'kode' => $iku->kode,
                    'deskripsi' => $iku->deskripsi,
                    'tim' => $iku->timKerja->pluck('nama_tim')->join(', ') ?: '—',
                    'masalah' => 'Belum Diisi',
                ]);

            $ikuBermasalah = $bermasalahTerisi->merge($belumDiisi)->values();
        }

        $trenMahasiswa = JumlahMahasiswa::with('tahunAnggaran')->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        $trenPts = JumlahPts::with('tahunAnggaran')->get()
            ->groupBy(fn ($r) => $r->tahunAnggaran->tahun)
            ->map(fn ($rows) => $rows->sum('jumlah'))
            ->sortKeys();

        $sebaranTim = $ikuList
            ->flatMap(fn ($iku) => $iku->timKerja->isNotEmpty() ? $iku->timKerja->pluck('nama_tim') : collect(['Tanpa Tim Kerja']))
            ->countBy();

        return compact('jumlahSasaran', 'jumlahIku', 'triwulanAktif', 'rataCapaian', 'ikuBermasalah', 'trenMahasiswa', 'trenPts', 'sebaranTim');
    }
}
