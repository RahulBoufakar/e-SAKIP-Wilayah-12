<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\AppliesIkuTimFilter;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\JumlahPublikasi;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\User;
use App\Services\CapaianKinerjaHitungService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
/**
 * Refaktor Capaian Kinerja Hybrid — Spek §8/§9. Satu controller melayani
 * seluruh 9 tipe_iku sekaligus: bentuk kolom yang berbeda ditangani lewat
 * config('capaian_kinerja_tipe') (presentasi & validasi dinamis) dan
 * CapaianKinerja::relasi()/komponenUntukTipe() (data) — BUKAN 9 controller
 * terpisah, karena alur CRUD baris (tambah/ubah/hapus/kirim) identik di
 * kesembilannya; yang membedakan hanya bentuk kolom, dan itu sudah
 * didelegasikan ke config + validasi dinamis di validasiBaris().
 *
 * Gerbang Triwulan Aktif ditegakkan SERVER-SIDE di guardTriwulanAktif() —
 * ini perbaikan dibanding controller lama (versi formula) yang hanya
 * menonaktifkan tombol di UI tanpa validasi ulang di server.
 */
class CapaianKinerjaController extends Controller
{
    use ResolvesTimKerjaSession;
    use AppliesIkuTimFilter;

    private const FILE_RULE = 'file|mimes:pdf|mimetypes:application/pdf|max:5120'; // PDF, maks 5 MB
    private const FIELD_FILE = ['file_bukti_dukung', 'file_implementasi_ppks_antinarkoba_antikorupsi'];

    public function __construct(private CapaianKinerjaHitungService $hitungService)
    {
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
        $this->authorize('manageKinerja', $iku);
        abort_unless($iku->tipe_iku, 404);

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
            ? \App\Models\Pts::orderBy('nama_pts')->get(['id', 'kode_pts', 'nama_pts'])
            : collect();

        $ringkasanMigrasi = $isTriwulanAktif ? $this->ringkasanMigrasi($capaian) : null;
        $masalahFile = $isTriwulanAktif ? $this->masalahFile($capaian) : collect();

        $view = $iku->tipe_iku === 'arsitektur_pts'
            ? 'tim-kerja.capaian-kinerja.tipe.arsitektur-pts'
            : 'tim-kerja.capaian-kinerja.tipe.generik';

        return view($view, compact('iku', 'capaian', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif', 'config', 'ptsOptions', 'ringkasanMigrasi', 'masalahFile'));
    }

    // POST /tim-kerja/capaian-kinerja/{iku}/baris/{komponen}
    public function storeBaris(Request $request, Iku $iku, string $komponen)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);

        // Entri tunggal (IKU 1.1, 1.3, 4.1): tolak INSERT kedua di server, walau tombol di-bypass
        if (config("capaian_kinerja_tipe.{$iku->tipe_iku}.entri_tunggal") && $capaian->relasi($komponen)->exists()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'IKU ini hanya boleh memiliki satu data per triwulan. Silakan edit data yang sudah ada.']);
        }

        $config = $this->configKomponen($iku->tipe_iku, $komponen);
        $data = $this->validasiBaris($request, $config, $capaian, $iku->tipe_iku, $komponen);
        $file = $this->simpanFile($request, $this->fileFields($config));

        try {
            $capaian->relasi($komponen)->create($data + $file);
        } catch (QueryException $e) {
            Storage::disk('private')->delete(array_values($file));

            return $this->responsDuplikat($e); // jaring pengaman: unique constraint DB (mis. dua request bersamaan)
        }

        event(new ActivityOccurred(
            subject: $capaian,
            description: "menambahkan data pada IKU {$iku->kode} — {$capaian->triwulan->kode}",
            causer: Auth::user(),
        ));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil ditambahkan.']);
    }

    public function updateBaris(Request $request, Iku $iku, string $komponen, int $barisId)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);

        $baris = $capaian->relasi($komponen)->findOrFail($barisId);
        if ($baris->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat diubah.']);
        }

        $config = $this->configKomponen($iku->tipe_iku, $komponen);
        $data = $this->validasiBaris($request, $config, $capaian, $iku->tipe_iku, $komponen, $barisId);
        $file = $this->simpanFile($request, $this->fileFields($config));
        $fileLama = Arr::only($baris->getAttributes(), array_keys($file));

        try {
            // Diedit di triwulan ini = milik triwulan ini: putus tautan sinkronisasi migrasi.
            // (Hanya untuk model yang punya kolomnya; 3 tabel entri tunggal tidak.)
            $putusTautan = in_array('sumber_baris_id', $baris->getFillable(), true) ? ['sumber_baris_id' => null] : [];
            $baris->update($data + $file + $putusTautan);
        } catch (QueryException $e) {
            Storage::disk('private')->delete(array_values($file));

            return $this->responsDuplikat($e);
        }

        // Hapus file lama yang tidak lagi dirujuk di baris manapun (mis. file_bukti_dukung)
        $this->hapusFileBilaTakDirujuk($iku, $komponen, $fileLama, $this->fileFields($config));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil diperbarui.']);
    }

    // DELETE /tim-kerja/capaian-kinerja/{iku}/baris/{komponen}/{barisId}
    public function destroyBaris(Request $request, Iku $iku, string $komponen, int $barisId)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);

        $baris = $capaian->relasi($komponen)->getQuery()->findOrFail($barisId);

        if ($baris->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat dihapus.']);
        }

        $fields = $this->fileFields($this->configKomponen($iku->tipe_iku, $komponen));
        $paths = Arr::only($baris->getAttributes(), $fields);
        $baris->delete();
        $this->hapusFileBilaTakDirujuk($iku, $komponen, $paths, $fields);
        
        $baris->delete();

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil dihapus.']);
    }

    // GET /tim-kerja/capaian-kinerja/{iku}/preview-kirim — hitung realisasi_otomatis TANPA mengubah status baris
    public function previewKirim(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);

        return response()->json($this->hitungService->ringkasan($capaian, ['draft', 'ditolak', 'menunggu_validasi', 'disetujui']));
}

    // PUT /tim-kerja/capaian-kinerja/{iku}/kirim
    public function kirim(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);

        $data = $request->validate([
            'realisasi_override_value' => ['nullable', 'numeric', 'min:0', 'lte:'.$iku->target_pk],
        ], [
            'realisasi_override_value.lte' => 'Realisasi tidak boleh melebihi Target PK ('.$iku->target_pk.').',
        ]);

        $wajibBermasalah = $this->masalahFile($capaian)->where('jenis', 'wajib');
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

    // PUT /tim-kerja/capaian-kinerja/{iku}/jumlah-publikasi — pembagi IKU 3.3 per triwulan
    public function simpanJumlahPublikasi(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);
        abort_unless($iku->tipe_iku === 'fasilitasi_penelitian', 404);

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

    // POST /tim-kerja/capaian-kinerja/{iku}/migrasi-triwulan
    public function migrasiTriwulan(Request $request, Iku $iku)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku);
        $this->guardTriwulanAktif($capaian);

        abort_if(config("capaian_kinerja_tipe.{$iku->tipe_iku}.entri_tunggal"), 422, 'IKU ini tidak mendukung migrasi triwulan.');

        $sumber = $this->headerTriwulanSebelumnya($capaian);
        if (! $sumber) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Tidak ada data triwulan sebelumnya untuk dimigrasi.']);
        }

        [$rencana, $yatim] = DB::transaction(function () use ($capaian, $sumber, $iku) {
            // Kunci header tujuan: klik ganda menunggu di sini, lalu melihat hasil klik pertama.
            CapaianKinerja::whereKey($capaian->id)->lockForUpdate()->firstOrFail();

            $rencana = $this->rencanaMigrasi($capaian, $sumber);
            $yatim = [];

            foreach ($rencana['perbarui'] as [$komponen, $baris, $salinan]) {
                $fields = $this->fileFields($this->configKomponen($iku->tipe_iku, $komponen));
                $yatim[] = [$komponen, Arr::only($salinan->getRawOriginal(), $fields), $fields];
                $salinan->update($this->dataMigrasi($baris)); // status salinan tidak disentuh
            }

            foreach ($rencana['baru'] as [$komponen, $baris]) {
                // Path file ikut tersalin apa adanya => shared reference, tanpa duplikasi fisik.
                $capaian->relasi($komponen)->create(
                    $this->dataMigrasi($baris) + ['sumber_baris_id' => $baris->id, 'status_validasi' => 'draft']
                );
            }

            return [$rencana, $yatim];
        });

        // File lama salinan yang diperbarui dibersihkan SETELAH commit (hanya jika tak lagi dirujuk siapa pun).
        foreach ($yatim as [$komponen, $paths, $fields]) {
            $this->hapusFileBilaTakDirujuk($iku, $komponen, $paths, $fields);
        }

        $baru = count($rencana['baru']);
        $diperbarui = count($rencana['perbarui']);
        $pesan = implode(', ', array_filter([
            $baru ? "{$baru} data baru disalin" : null,
            $diperbarui ? "{$diperbarui} data diperbarui" : null,
            $rencana['terkunci'] ? "{$rencana['terkunci']} data sudah divalidasi dan berbeda dari triwulan sebelumnya (tidak diubah)" : null,
            $rencana['bentrok'] ? "{$rencana['bentrok']} data tidak dapat diperbarui karena kuncinya bentrok dengan data lain" : null,
            $rencana['dilewati'] ? "{$rencana['dilewati']} data dilewati (sudah ada)" : null,
        ]));

        if ($baru + $diperbarui === 0) {
            return back()->with('feedback', [
                'type' => 'error',
                'message' => $pesan ? ucfirst($pesan).'.' : 'Tidak ada data tervalidasi di triwulan sebelumnya untuk dimigrasi.',
            ]);
        }

        event(new ActivityOccurred(
            subject: $capaian,
            description: "memigrasi Capaian Kinerja IKU {$iku->kode} dari triwulan sebelumnya ke {$capaian->triwulan->kode} ({$baru} baru, {$diperbarui} diperbarui)",
            causer: Auth::user(),
        ));

        return back()->with('feedback', [
            'type' => 'success',
            'message' => ucfirst($pesan).'. Data hasil migrasi berstatus draft; periksa bukti dukung lalu kirim untuk validasi.',
        ]);
    }

    /**
     * Pencocokan tiap baris `disetujui` triwulan sebelumnya:
     * 1. Ada salinan bertaut (sumber_baris_id):
     *    - data sama                       -> dilewati
     *    - salinan menunggu/disetujui      -> terkunci (hanya dilaporkan)
     *    - kunci baru bentrok baris lain   -> bentrok (dilaporkan)
     *    - selain itu                      -> perbarui
     * 2. Tanpa salinan, tapi kunci unik sama dengan baris mandiri di tujuan -> dilewati (tidak diadopsi/ditimpa)
     * 3. Selain itu -> baru
     * Pengecekan lewat query DB (bukan banding di PHP) supaya collation sama dengan unique constraint.
     */
    private function rencanaMigrasi(CapaianKinerja $capaian, CapaianKinerja $sumber): array
    {
        $kunci = CapaianKinerja::kunciUnik($capaian->iku->tipe_iku);
        $rencana = ['baru' => [], 'perbarui' => [], 'terkunci' => 0, 'bentrok' => 0, 'dilewati' => 0];

        foreach (array_keys($capaian->iku->komponenCapaian()) as $komponen) {
            foreach ($sumber->relasi($komponen)->disetujui()->get() as $baris) {
                $kunciBaris = Arr::only($baris->getRawOriginal(), $kunci);
                $salinan = $capaian->relasi($komponen)->where('sumber_baris_id', $baris->id)->first();

                if (! $salinan) {
                    if ($capaian->relasi($komponen)->where($kunciBaris)->exists()) {
                        $rencana['dilewati']++;
                    } else {
                        $rencana['baru'][] = [$komponen, $baris];
                    }
                    continue;
                }

                if ($this->dataMigrasi($salinan) === $this->dataMigrasi($baris)) {
                    $rencana['dilewati']++;
                    continue;
                }

                if (in_array($salinan->status_validasi, ['menunggu_validasi', 'disetujui'], true)) {
                    $rencana['terkunci']++;
                    continue;
                }

                if ($capaian->relasi($komponen)->where($kunciBaris)->where('id', '!=', $salinan->id)->exists()) {
                    $rencana['bentrok']++;
                    continue;
                }

                $rencana['perbarui'][] = [$komponen, $baris, $salinan];
            }
        }

        return $rencana;
    }

    /** Kolom data yang disalin/dibandingkan (tanpa identitas, status, tautan, timestamp). */
    private function dataMigrasi(Model $baris): array
    {
        return Arr::except($baris->getRawOriginal(), [
            'id', 'capaian_kinerja_id', 'sumber_baris_id', 'status_validasi', 'catatan_revisi', 'created_at', 'updated_at',
        ]);
    }

    /** Untuk tombol/keterangan di view. Semua nol = tidak ada yang perlu ditampilkan. */
    private function ringkasanMigrasi(CapaianKinerja $capaian): array
    {
        $kosong = ['baru' => 0, 'perbarui' => 0, 'terkunci' => 0, 'bentrok' => 0, 'dilewati' => 0];

        if (config("capaian_kinerja_tipe.{$capaian->iku->tipe_iku}.entri_tunggal")) {
            return $kosong;
        }

        $sumber = $this->headerTriwulanSebelumnya($capaian);
        if (! $sumber) {
            return $kosong;
        }

        $rencana = $this->rencanaMigrasi($capaian, $sumber);

        return [
            'baru' => count($rencana['baru']),
            'perbarui' => count($rencana['perbarui']),
        ] + Arr::only($rencana, ['terkunci', 'bentrok', 'dilewati']);
    }

    /** Header capaian (IKU, TA sama) untuk triwulan urutan-1; null bila TW1 atau belum ada. */
    private function headerTriwulanSebelumnya(CapaianKinerja $capaian): ?CapaianKinerja
    {
        $sebelumnyaId = Triwulan::where('urutan', $capaian->triwulan->urutan - 1)->value('id');
        if (! $sebelumnyaId) {
            return null;
        }

        $header = CapaianKinerja::where('iku_id', $capaian->iku_id)
            ->where('tahun_anggaran_id', $capaian->tahun_anggaran_id)
            ->where('triwulan_id', $sebelumnyaId)
            ->first();
        $header?->setRelation('iku', $capaian->iku);

        return $header;
    }

    /**
     * Hapus file fisik hanya jika tidak ada baris lain di tabel komponen yang sama
     * yang masih merujuk path itu (file dipakai bersama antar triwulan hasil migrasi).
     * Panggil SETELAH baris dihapus/diupdate.
     */
    private function hapusFileBilaTakDirujuk(Iku $iku, string $komponen, array $paths, array $fields): void
    {
        $model = CapaianKinerja::komponenUntukTipe($iku->tipe_iku)[$komponen];

        foreach (array_filter($paths) as $path) {
            $masihDirujuk = $model::where(function ($q) use ($fields, $path) {
                foreach ($fields as $field) {
                    $q->orWhere($field, $path);
                }
            })->exists();

            if (! $masihDirujuk) {
                Storage::disk('private')->delete($path);
            }
        }
    }

    /**
     * Baris draft/ditolak (yang akan dikirim) dengan file bermasalah.
     * jenis 'wajib'  = file wajib belum ada / hilang dari disk  -> memblokir kirim().
     * jenis 'hilang' = file opsional berpath tapi hilang dari disk -> peringatan saja.
     */
    private function masalahFile(CapaianKinerja $capaian): Collection
    {
        $tipe = $capaian->iku->tipe_iku;
        $masalah = collect();

        foreach (array_keys($capaian->iku->komponenCapaian()) as $komponen) {
            $config = $this->configKomponen($tipe, $komponen);
            $butuhPts = $config['butuh_pts'] ?? false;
            $kolom = collect($config['kolom']);

            $wajib = $kolom->where('tipe', 'file')->where('required', true)->pluck('field')->all();
            if ($config['bukti_wajib'] ?? false) {
                $wajib[] = 'file_bukti_dukung';
            }
            $label = $kolom->pluck('label', 'field')->all() + ['file_bukti_dukung' => 'Bukti Dukung'];

            $query = $capaian->relasi($komponen)->getQuery()->whereIn('status_validasi', ['draft', 'ditolak']);
            if ($butuhPts) {
                $query->with('pts');
            }

            foreach ($query->get() as $baris) {
                $nidn = $baris->getAttributes()['nidn'] ?? null;
                $nama = trim(($butuhPts ? ($baris->pts->nama_pts ?? '') : '').($nidn ? " [NIDN {$nidn}]" : '')) ?: "Data #{$baris->id}";

                foreach ($this->fileFields($config) as $field) {
                    if ($baris->fileTersedia($field)) {
                        continue;
                    }

                    $isWajib = in_array($field, $wajib, true);
                    if ($isWajib || filled($baris->{$field})) {
                        $masalah->push(['baris' => $nama, 'field' => $label[$field] ?? $field, 'jenis' => $isWajib ? 'wajib' : 'hilang']);
                    }
                }
            }
        }

        return $masalah;
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
        $capaian = $this->resolveCapaianAktif($request, $iku); // di Validator: resolveCapaian
        $baris = $capaian->relasi($komponen)->findOrFail($barisId);

        $field = $request->query('field', 'file_bukti_dukung');
        abort_unless(in_array($field, self::FIELD_FILE, true), 404); // whitelist, cegah baca kolom sembarang

        $path = $baris->{$field};
        abort_unless($path && Storage::disk('private')->exists($path), 404);

        if ($download) {
            return Storage::disk('private')->download($path);
        }

        return response()->stream(function () use ($path) {
            fpassthru(Storage::disk('private')->readStream($path));
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

    private function resolveCapaianAktif(Request $request, Iku $iku): CapaianKinerja
    {
        $this->authorize('manageKinerja', $iku);

        $tahunAnggaranId = $this->activeTahunAnggaranId($request);
        $triwulanId = (int) $request->input('triwulan_id', $request->query('triwulan_id'));

        $capaian = CapaianKinerja::where('iku_id', $iku->id)
            ->where('tahun_anggaran_id', $tahunAnggaranId)
            ->where('triwulan_id', $triwulanId)
            ->firstOrFail();
        $capaian->setRelation('iku', $iku);

        return $capaian;
    }

    /**
     * Perbaikan dibanding controller lama: gerbang Triwulan Aktif ditegakkan
     * di SERVER, bukan hanya UI (lihat catatan review di ringkasan PR).
     */
    private function guardTriwulanAktif(CapaianKinerja $capaian): void
    {
        $triwulanAktifStatus = TriwulanStatus::where('tahun_anggaran_id', $capaian->tahun_anggaran_id)
            ->where('status', 'aktif')->first();

        abort_unless(
            $triwulanAktifStatus && $triwulanAktifStatus->triwulan_id === $capaian->triwulan_id,
            403,
            'Data Capaian Kinerja hanya dapat diubah pada Triwulan yang sedang aktif.'
        );
    }

    private function validasiBaris(Request $request, array $config, CapaianKinerja $capaian, string $tipeIku, string $komponen, ?int $barisId = null): array
    {
        $membuat = $request->isMethod('post');
        $rules = [];

        foreach ($config['kolom'] as $k) {
            $aturan = match ($k['tipe']) {
                'number'         => 'integer|min:0',
                'number_decimal' => 'numeric|min:0',
                'date'           => 'date',
                'select'         => 'string|in:'.implode(',', $k['opsi']),
                'file'           => self::FILE_RULE,
                default          => 'string',
            };
            $wajib = $k['required'] && ($k['tipe'] !== 'file' || $membuat); // file: wajib saat tambah, opsional saat edit

            $rules[$k['field']] = [
                $wajib ? 'required' : 'nullable',
                ...explode('|', $aturan),
                ...(isset($k['rule']) ? explode('|', $k['rule']) : []),
            ];
        }

        if ($config['butuh_pts'] ?? false) {
            $rules['pts_id'] = ['required', 'exists:pts,id'];
        }

        if (($config['bukti'] ?? true) && ! isset($rules['file_bukti_dukung'])) {
            $rules['file_bukti_dukung'] = [
                (($config['bukti_wajib'] ?? false) && $membuat) ? 'required' : 'nullable',
                ...explode('|', self::FILE_RULE),
            ];
        }

        foreach ($this->aturanUnik($request, $capaian, $tipeIku, $komponen, $barisId) as $field => $aturanUnik) {
            $rules[$field][] = $aturanUnik;
        }

        $validated = ValidatorFacade::make(
            $request->all(),
            $rules,
            ['unique' => 'Data yang sama sudah tercatat pada triwulan ini.', 'mimes' => 'File harus berformat PDF.', 'mimetypes' => 'File harus berformat PDF.', 'max' => 'Ukuran file maksimal 5 MB.'],
            collect($config['kolom'])->pluck('label', 'field')->all()
        )->validate();

        return Arr::except($validated, $this->fileFields($config)); // file disimpan terpisah lewat simpanFile()
    }

    /** Pencegahan duplikasi di level aplikasi (constraint DB = lapis kedua). Daftar kunci: CapaianKinerja::kunciUnik(). */
    private function aturanUnik(Request $request, CapaianKinerja $capaian, string $tipeIku, string $komponen, ?int $barisId): array
    {
        $kunci = CapaianKinerja::kunciUnik($tipeIku);
        if (! $kunci) {
            return [];
        }

        $tabel = (new (CapaianKinerja::komponenUntukTipe($tipeIku)[$komponen]))->getTable();
        $utama = array_shift($kunci); // kolom pertama = tempat aturan unique; sisanya = scope

        return [
            $utama => Rule::unique($tabel, $utama)
                ->where(fn ($q) => $q->where('capaian_kinerja_id', $capaian->id)->where($request->only($kunci)))
                ->ignore($barisId),
        ];
    }

    private function responsDuplikat(QueryException $e): RedirectResponse
    {
        if ((int) $e->getCode() !== 23000) {
            throw $e;
        }

        return back()->with('feedback', ['type' => 'error', 'message' => 'Data yang sama sudah tercatat atau IKU ini hanya boleh satu data per triwulan.']);
    }

    /** Config khusus IKU 2 (arsitektur_pts) — 2 tabel berbeda kolom per komponen. */
    private function configKomponenArsitekturPts(string $komponen): ?array
    {
        return match ($komponen) {
            'akreditasi' => [
                'butuh_pts' => true,
                'kolom' => [
                    ['field' => 'akreditasi', 'label' => 'Akreditasi', 'tipe' => 'text', 'required' => true],
                    ['field' => 'no_sk', 'label' => 'No. SK', 'tipe' => 'text', 'required' => true],
                    ['field' => 'masa_berlaku', 'label' => 'Masa Berlaku', 'tipe' => 'date', 'required' => true],
                ],
            ],
            'penggabungan' => [
                'butuh_pts' => true,
                'kolom' => [
                    ['field' => 'sk_penggabungan', 'label' => 'No. SK Penggabungan', 'tipe' => 'text', 'required' => true],
                    ['field' => 'file_bukti_dukung', 'label' => 'Bukti Dokumen SK Penggabungan PTS', 'tipe' => 'file', 'required' => true, 'link_teks' => 'SK'],
                ],
            ],
            default => null,
        };
    }

    private function labelAtribut(array $kolom): array
    {
        return collect($kolom)->pluck('label', 'field')->all();
    }

    private function configKomponen(string $tipeIku, string $komponen): array
    {
        $config = $tipeIku === 'arsitektur_pts'
            ? $this->configKomponenArsitekturPts($komponen)
            : config("capaian_kinerja_tipe.{$tipeIku}");

        abort_unless($config, 404);

        return $config;
    }

    /** Semua field yang berupa upload file untuk komponen ini. */
    private function fileFields(array $config): array
    {
        $fields = collect($config['kolom'])->where('tipe', 'file')->pluck('field')->all();

        if (($config['bukti'] ?? true) && ! in_array('file_bukti_dukung', $fields, true)) {
            $fields[] = 'file_bukti_dukung';
        }

        return $fields;
    }

    private function simpanFile(Request $request, array $fields): array
    {
        $hasil = [];
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $hasil[$field] = $request->file($field)->store('capaian-kinerja-hybrid', 'private');
            }
        }

        return $hasil;
    }
}