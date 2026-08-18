<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Realisasi;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use Illuminate\Http\Request;

class RealisasiController extends Controller
{
    private const TRIWULAN_VALID = ['tw1', 'tw2', 'tw3', 'tw4'];

    // GET /admin/iku-lldikti
    public function index(Request $request)
    {
        $tahunAnggaranId = $request->session()->get('tahun_anggaran_id')
            ?: optional(TahunAnggaran::orderByDesc('tahun')->first())->id;

        $triwulan = in_array($request->get('triwulan'), self::TRIWULAN_VALID, true)
            ? $request->get('triwulan')
            : 'tw1';

        $sasaranList = SasaranKegiatan::with(['iku' => function ($q) use ($triwulan) {
                $q->with(['timKerja', 'realisasis' => fn ($r) => $r->where('triwulan', $triwulan)])
                ->orderBy('kode');
            }])
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->orderBy('kode')
            ->get();

        return view('admin.target-kinerja.iku-lldikti.index', compact('sasaranList', 'triwulan'));
    }

    // PUT /admin/realisasi — simpan/ubah target & realisasi 1 IKU untuk 1 triwulan
    public function storeOrUpdate(Request $request)
    {
        $data = $request->validate([
            'iku_id' => 'required|exists:iku,id',
            'triwulan' => 'required|in:tw1,tw2,tw3,tw4',
            'target' => 'nullable|numeric|min:0',
            'realisasi' => 'nullable|numeric|min:0',
        ], [
            'iku_id.required' => 'IKU wajib dipilih.',
            'iku_id.exists' => 'IKU tidak valid.',
            'triwulan.required' => 'Triwulan wajib dipilih.',
            'triwulan.in' => 'Triwulan tidak valid.',
            'target.numeric' => 'Target harus berupa angka.',
            'realisasi.numeric' => 'Realisasi harus berupa angka.',
        ]);

        Realisasi::updateOrCreate(
            ['iku_id' => $data['iku_id'], 'triwulan' => $data['triwulan']],
            ['target' => $data['target'] ?? null, 'realisasi' => $data['realisasi'] ?? null]
        );

        // TODO (belum dibuat): catat perubahan ke sistem audit log saat fitur itu tersedia.

        return back()->with('feedback', ['type' => 'success', 'message' => 'Target & Realisasi berhasil disimpan.']);
    }
}