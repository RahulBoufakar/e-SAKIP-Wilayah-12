<?php

namespace App\Services;

use App\Models\CapaianKinerja;
use App\Models\Triwulan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/** Migrasi baris `disetujui` triwulan sebelumnya ke triwulan aktif (pratinjau + eksekusi). */
class CapaianKinerjaMigrasiService
{
    public function __construct(private CapaianKinerjaBerkasService $berkas)
    {
    }

    /**
     * Eksekusi migrasi dalam satu transaksi. Mengembalikan rencana yang dijalankan
     * (baru/perbarui/terkunci/bentrok/dilewati) atau null bila tidak ada triwulan sebelumnya.
     */
    public function jalankan(CapaianKinerja $capaian): ?array
    {
        $sumber = $this->headerTriwulanSebelumnya($capaian);
        if (! $sumber) {
            return null;
        }

        $iku = $capaian->iku;

        [$rencana, $yatim] = DB::transaction(function () use ($capaian, $sumber, $iku) {
            // Kunci header tujuan: klik ganda menunggu di sini, lalu melihat hasil klik pertama.
            CapaianKinerja::whereKey($capaian->id)->lockForUpdate()->firstOrFail();

            $rencana = $this->rencanaMigrasi($capaian, $sumber);
            $yatim = [];

            foreach ($rencana['perbarui'] as [$komponen, $baris, $salinan]) {
                $fields = CapaianKinerja::fileFields(CapaianKinerja::configKomponen($iku->tipe_iku, $komponen));
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
            $this->berkas->hapusFileBilaTakDirujuk($iku, $komponen, $paths, $fields);
        }

        return $rencana;
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
    public function ringkasan(CapaianKinerja $capaian): array
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
}
