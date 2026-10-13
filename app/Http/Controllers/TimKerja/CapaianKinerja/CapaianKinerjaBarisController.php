<?php

namespace App\Http\Controllers\TimKerja\CapaianKinerja;

use App\Events\ActivityOccurred;
use App\Http\Controllers\Concerns\ResolvesCapaianAktif;
use App\Http\Controllers\Concerns\ResolvesTimKerjaSession;
use App\Http\Controllers\Controller;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Services\CapaianKinerjaBerkasService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;

/**
 * CRUD baris detail Capaian Kinerja (semua tipe_iku). Bentuk kolom per tipe
 * ditangani lewat config + validasi dinamis di validasiBaris(). Gerbang
 * Triwulan Aktif & kepemilikan: CapaianKinerjaPolicy::update.
 */
class CapaianKinerjaBarisController extends Controller
{
    use ResolvesTimKerjaSession;
    use ResolvesCapaianAktif;

    private const FILE_RULE = 'file|mimes:pdf|mimetypes:application/pdf|max:5120'; // PDF, maks 5 MB

    public function __construct(private CapaianKinerjaBerkasService $berkas)
    {
    }

    // POST /tim-kerja/capaian-kinerja/{iku}/baris/{komponen}
    public function store(Request $request, Iku $iku, string $komponen)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'update');

        // Entri tunggal (IKU 1.1, 1.3, 4.1): tolak INSERT kedua di server, walau tombol di-bypass
        if (config("capaian_kinerja_tipe.{$iku->tipe_iku}.entri_tunggal") && $capaian->relasi($komponen)->exists()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'IKU ini hanya boleh memiliki satu data per triwulan. Silakan edit data yang sudah ada.']);
        }

        $config = $this->configKomponen($iku->tipe_iku, $komponen);
        $data = $this->validasiBaris($request, $config, $capaian, $iku->tipe_iku, $komponen);
        $file = $this->berkas->simpanFile($request, CapaianKinerja::fileFields($config));

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

    // PUT /tim-kerja/capaian-kinerja/{iku}/baris/{komponen}/{barisId}
    public function update(Request $request, Iku $iku, string $komponen, int $barisId)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'update');

        $baris = $capaian->relasi($komponen)->findOrFail($barisId);
        if ($baris->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat diubah.']);
        }

        $config = $this->configKomponen($iku->tipe_iku, $komponen);
        $data = $this->validasiBaris($request, $config, $capaian, $iku->tipe_iku, $komponen, $barisId);
        $file = $this->berkas->simpanFile($request, CapaianKinerja::fileFields($config));
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
        $this->berkas->hapusFileBilaTakDirujuk($iku, $komponen, $fileLama, CapaianKinerja::fileFields($config));

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil diperbarui.']);
    }

    // DELETE /tim-kerja/capaian-kinerja/{iku}/baris/{komponen}/{barisId}
    public function destroy(Request $request, Iku $iku, string $komponen, int $barisId)
    {
        $capaian = $this->resolveCapaianAktif($request, $iku, 'update');

        $baris = $capaian->relasi($komponen)->getQuery()->findOrFail($barisId);

        if ($baris->isFieldLocked()) {
            return back()->with('feedback', ['type' => 'error', 'message' => 'Data ini sedang terkunci dan tidak dapat dihapus.']);
        }

        $fields = CapaianKinerja::fileFields($this->configKomponen($iku->tipe_iku, $komponen));
        $paths = Arr::only($baris->getAttributes(), $fields);
        $baris->delete();
        $this->berkas->hapusFileBilaTakDirujuk($iku, $komponen, $paths, $fields);

        return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil dihapus.']);
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
            [
                'unique' => 'Data yang sama sudah tercatat pada triwulan ini.', 'mimes' => 'File harus berformat PDF.', 'mimetypes' => 'File harus berformat PDF.',
                // 'max' global akan menimpa max:100 / max:16 pada kolom non-file, jadi khusus field file saja
                ...collect(CapaianKinerja::fileFields($config))->mapWithKeys(fn ($f) => ["{$f}.max" => 'Ukuran file maksimal 5 MB.'])->all(),
            ],
            collect($config['kolom'])->pluck('label', 'field')->all()
        )->validate();

        return Arr::except($validated, CapaianKinerja::fileFields($config)); // file disimpan terpisah lewat simpanFile()
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
}
