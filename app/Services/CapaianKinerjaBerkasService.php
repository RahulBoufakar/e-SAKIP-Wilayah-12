<?php

namespace App\Services;

use App\Models\CapaianKinerja;
use App\Models\Iku;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/** Urusan file bukti Capaian Kinerja: simpan, bersihkan, dan audit kelengkapan. */
class CapaianKinerjaBerkasService
{
    /**
     * Hapus file fisik hanya jika tidak ada baris lain di tabel komponen yang sama
     * yang masih merujuk path itu (file dipakai bersama antar triwulan hasil migrasi).
     * Panggil SETELAH baris dihapus/diupdate.
     */
    public function hapusFileBilaTakDirujuk(Iku $iku, string $komponen, array $paths, array $fields): void
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
    public function masalahFile(CapaianKinerja $capaian): Collection
    {
        $tipe = $capaian->iku->tipe_iku;
        $masalah = collect();

        foreach (array_keys($capaian->iku->komponenCapaian()) as $komponen) {
            $config = CapaianKinerja::configKomponen($tipe, $komponen);
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

                foreach (CapaianKinerja::fileFields($config) as $field) {
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

    public function simpanFile(Request $request, array $fields): array
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
