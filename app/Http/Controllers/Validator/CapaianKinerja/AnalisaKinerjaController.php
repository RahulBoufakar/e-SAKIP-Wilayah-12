<?php

namespace App\Http\Controllers\Validator\CapaianKinerja;

use App\Http\Controllers\Concerns\ResolvesActiveTahunAnggaran;
use App\Http\Controllers\Controller;
use App\Models\AnalisaKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use RuntimeException;

class AnalisaKinerjaController extends Controller
{
    use ResolvesActiveTahunAnggaran;

    // GET /validator/analisa-kinerja?triwulan=TW1..TW4 — seluruh Tim Kerja, tidak difilter
    public function index(Request $request)
    {
        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        if (! $tahunAnggaranId) {
            return $this->missingTahunAnggaran('validator.layout.app', 'validator.dashboard');
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
            $ikuList = Iku::with(['timKerja', 'analisaKinerja' => fn ($q) => $q->where('triwulan_id', $triwulanDipilih->id)
                    ->where('tahun_anggaran_id', $tahunAnggaranId)])
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get();
        }

        return view('validator.capaian-kinerja.analisa-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
    }

    // PUT /validator/analisa-kinerja/{analisaKinerja}/validasi
    public function validasi(Request $request, AnalisaKinerja $analisaKinerja)
    {
        if (Auth::user()->cannot('validasi', $analisaKinerja)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Analisis Kinerja hanya dapat divalidasi saat berstatus menunggu validasi pada Triwulan yang sedang aktif.']);
        }

        $validator = Validator::make($request->all(), [
            'status' => ['required', Rule::in(['disetujui', 'ditolak'])],
            'catatan_revisi' => 'required_if:status,ditolak|nullable|string',
        ], [
            'status.required' => 'Status wajib dipilih.',
            'catatan_revisi.required_if' => 'Catatan revisi wajib diisi saat menolak.',
        ]);

        if ($validator->fails()) {
            return back()->with('feedback', ['type' => 'error', 'message' => $validator->errors()->first()]);
        }

        $data = $validator->validated();

        try {
            if ($data['status'] === 'disetujui') {
                $analisaKinerja->setujui();
            } else {
                $analisaKinerja->tolak($data['catatan_revisi']);
            }
        } catch (RuntimeException $e) {
            return back()->with('feedback', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back()->with('feedback', ['type' => 'success', 'message' => 'Status Analisis Kinerja berhasil disimpan.']);
    }
}