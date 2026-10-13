<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\JumlahPublikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JumlahPublikasiController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    // PUT /tim-kerja/capaian-kinerja/{iku}/jumlah-publikasi — pembagi IKU 3.3 per triwulan
    public function __invoke(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'updateJumlahPublikasi');

        if (in_array($capaian->status, ['menunggu_validasi', 'disetujui'], true)) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Jumlah publikasi terkunci karena data sedang menunggu validasi atau sudah disetujui.']);
        }

        // Error bag terpisah supaya $errors->any() di halaman tidak ikut membuka modal Tambah Data.
        $data = $request->validateWithBag('jumlahPublikasi', [
            'jumlah' => 'required|integer|min:0',
        ], [
            'jumlah.required' => 'Jumlah publikasi wajib diisi.',
            'jumlah.integer' => 'Jumlah publikasi harus berupa bilangan bulat.',
            'jumlah.min' => 'Jumlah publikasi tidak boleh negatif.',
        ]);

        $lama = $capaian->jumlahPublikasi()->value('jumlah');
        $lama = $lama !== null ? (int) $lama : null;

        JumlahPublikasi::updateOrCreate(
            ['capaian_kinerja_id' => $capaian->id],
            [
                'tahun_anggaran_id' => $capaian->tahun_anggaran_id,
                'jumlah' => (int) $data['jumlah'],
                'diperbarui_oleh' => Auth::id(),
            ]
        );

        event(new ActivityOccurred(
            subject: $capaian,
            description: "mengubah Jumlah Publikasi IKU {$iku->kode} — {$capaian->triwulan->kode}: ".($lama ?? '—').' → '.(int) $data['jumlah'],
            causer: Auth::user(),
            properties: ['jumlah_lama' => $lama, 'jumlah_baru' => (int) $data['jumlah']],
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Jumlah publikasi berhasil disimpan.']);
    }
}
