<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\Iku;
use App\Models\User;
use App\Services\CapaianKinerjaBerkasService;
use App\Services\CapaianKinerjaHitungService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CapaianKinerjaKirimController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    public function __construct(
        private CapaianKinerjaHitungService $hitungService,
        private CapaianKinerjaBerkasService $berkas,
    ) {
    }

    // GET /tim-kerja/capaian-kinerja/{iku}/preview-kirim — hitung realisasi_otomatis TANPA mengubah status baris
    public function preview(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);

        return response()->json($this->hitungService->ringkasan($capaian, ['draft', 'ditolak', 'menunggu_validasi', 'disetujui']));
    }

    // PUT /tim-kerja/capaian-kinerja/{iku}/kirim
    public function kirim(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'update');

        $data = $request->validate([
            'realisasi_override_value' => ['nullable', 'numeric', 'min:0', 'lte:'.$iku->target_pk],
        ], [
            'realisasi_override_value.lte' => 'Realisasi tidak boleh melebihi Target PK ('.$iku->target_pk.').',
        ]);

        $wajibBermasalah = $this->berkas->masalahFile($capaian)->where('jenis', 'wajib');
        if ($wajibBermasalah->isNotEmpty()) {
            $daftar = $wajibBermasalah->take(5)->map(fn ($m) => "{$m['baris']} ({$m['field']})")->implode('; ');
            $sisa = $wajibBermasalah->count() - 5;

            return back()->with('feedback', ['type' => 'error', 'message' => 'Data belum dapat dikirim, bukti wajib belum lengkap: '.$daftar.($sisa > 0 ? " dan {$sisa} lainnya" : '').'. Unggah lewat tombol Edit pada baris terkait.']);
        }

        $jumlahDikirim = 0;
        foreach (array_keys($iku->komponenCapaian()) as $komponen) {
            $barisSiapKirim = $capaian->relasi($komponen)->getQuery()->whereIn('status_validasi', ['draft', 'ditolak'])->get();
            foreach ($barisSiapKirim as $baris) {
                $baris->kirim();
                $jumlahDikirim++;
            }
        }

        if ($jumlahDikirim === 0) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tidak ada data baru untuk dikirim. Tambahkan data terlebih dahulu.']);
        }

        $realisasiOtomatis = $this->hitungService->ringkasan($capaian, ['draft', 'ditolak', 'menunggu_validasi', 'disetujui'])['realisasi'];
        $adaOverride = array_key_exists('realisasi_override_value', $data) && $data['realisasi_override_value'] !== null;

        $capaian->realisasi_otomatis = $realisasiOtomatis;
        $capaian->realisasi = $adaOverride ? $data['realisasi_override_value'] : $realisasiOtomatis;
        $capaian->realisasi_override = $adaOverride;
        $capaian->syncStatusFromBaris();

        event(new ActivityOccurred(
            subject: $capaian,
            description: "mengirim {$jumlahDikirim} data Capaian Kinerja IKU {$iku->kode} — {$capaian->triwulan->kode} untuk validasi",
            causer: Auth::user(),
            recipients: User::role('validator')->get(),
            url: route('validator.capaian-kinerja.show', $iku->id),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil dikirim untuk validasi.']);
    }
}
