<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\User;
use App\Services\CapaianKinerjaHitungService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
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

    private const FILE_RULE = 'file|mimes:pdf|mimetypes:application/pdf|max:5120'; // PDF, maks 5 MB
    private const FIELD_FILE = ['file_bukti_dukung', 'file_implementasi_ppks', 'file_implementasi_anti_narkoba', 'file_implementasi_anti_korupsi'];

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
            $ikuList = Iku::whereNotNull('tipe_iku')
                ->whereHas('timKerja', fn ($q) => $q->whereIn('tim_kerja.id', $timKerjaIds))
                ->whereHas('sasaranKegiatan', fn ($q) => $q->where('tahun_anggaran_id', $tahunAnggaranId))
                ->orderBy('kode')
                ->get()
                ->map(fn (Iku $iku) => $this->tempelkanCapaianAktif($iku, $triwulanDipilih->id, $tahunAnggaranId));
        }

        return view('tim-kerja.capaian-kinerja.index', compact('ikuList', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif'));
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

        $view = $iku->tipe_iku === 'arsitektur_pts'
            ? 'tim-kerja.capaian-kinerja.tipe.arsitektur-pts'
            : 'tim-kerja.capaian-kinerja.tipe.generik';

        return view($view, compact('iku', 'capaian', 'triwulanList', 'triwulanDipilih', 'isTriwulanAktif', 'config', 'ptsOptions'));
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
            $baris->update($data + $file);
        } catch (QueryException $e) {
            Storage::disk('private')->delete(array_values($file));

            return $this->responsDuplikat($e);
        }

        Storage::disk('private')->delete(array_filter($fileLama)); // hapus file lama hanya setelah update sukses

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

        if ($baris->file_bukti_dukung) {
            Storage::disk('private')->delete($baris->file_bukti_dukung);
        }
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

    /** Pencegahan duplikasi di level aplikasi (constraint DB = lapis kedua). */
    private function aturanUnik(Request $request, CapaianKinerja $capaian, string $tipeIku, string $komponen, ?int $barisId): array
    {
        $tabel = (new (CapaianKinerja::komponenUntukTipe($tipeIku)[$komponen]))->getTable();
        $unik = fn (string $kolom, array $serta = []) => Rule::unique($tabel, $kolom)
            ->where(fn ($q) => $q->where('capaian_kinerja_id', $capaian->id)->where($serta))
            ->ignore($barisId);

        return match ($tipeIku) {
            'dosen_naik_jafung'                                   => ['nidn' => $unik('nidn')],
            'arsitektur_pts', 'kebijakan_ppks'                    => ['pts_id' => $unik('pts_id')],
            'fasilitasi_mutu_pts', 'fasilitasi_kemahasiswaan'     => ['pts_id' => $unik('pts_id', $request->only('bentuk_fasilitasi', 'tanggal_kegiatan'))],
            'fasilitasi_penelitian'                               => ['pts_id' => $unik('pts_id', $request->only('nidn', 'bentuk_fasilitasi'))],
            default                                               => [],
        };
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