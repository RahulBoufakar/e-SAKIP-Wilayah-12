<?php

namespace App\Services;

use App\Models\FileExcel;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Menyimpan hasil RabMasterAnalyzer ke tabel sheet/header/footer/kategori/kelompok (03).
 * Footer/header yang kosong (null dari analyzer) disimpan tanpa baris.
 */
class RabMasterStore
{
    public function __construct(private RabMasterAnalyzer $analyzer)
    {
    }

    /**
     * @param  array{sheets: array<int, array>, peringatan: string[]}  $hasil
     *
     * @throws RuntimeException bila hasil tidak dapat disimpan (tidak ada sheet, atau kode komponen ganda
     *                          yang melanggar unik per sheet). Tidak ada yang ditulis ke database.
     */
    public function simpan(FileExcel $file, array $hasil): void
    {
        $this->pastikanDapatDisimpan($hasil);

        DB::transaction(function () use ($file, $hasil) {
            $peringatan = ['file' => $hasil['peringatan'], 'sheet' => []];

            foreach ($hasil['sheets'] as $s) {
                $sheet = $file->sheets()->create([
                    'kode_sheet' => $s['kode_sheet'],
                    'nama_sheet' => $s['nama_sheet'],
                    'urutan' => $s['urutan'],
                    'ro_baris_awal' => $s['ro_baris_awal'],
                    'ro_baris_akhir' => $s['ro_baris_akhir'],
                    'kolom_akhir' => $s['kolom_akhir'],
                ]);

                foreach (['header', 'footer'] as $blok) {
                    if ($s[$blok] !== null) {
                        $sheet->{$blok}()->create($s[$blok] + ['otomatis' => true]);
                    }
                }

                foreach ($s['kategori'] as $k) {
                    $kategori = $sheet->kategori()->create(collect($k)->except('kelompok')->all());
                    $kategori->kelompok()->createMany($k['kelompok']);
                }

                // Hanya peringatan yang tidak bisa dihitung ulang dari database.
                $peringatan['sheet'][$s['kode_sheet']] = array_values(
                    array_diff($s['peringatan'], $this->analyzer->peringatanStruktur($s['kategori']))
                );
            }

            $file->update(['peringatan' => $peringatan]);
        });
    }

    private function pastikanDapatDisimpan(array $hasil): void
    {
        if ($hasil['sheets'] === []) {
            throw new RuntimeException('Tidak ada sheet dengan kode RO di kolom A. Pastikan ini file master RAB yang benar.');
        }

        foreach ($hasil['sheets'] as $s) {
            $kode = array_count_values(array_column($s['kategori'], 'kode_kategori'));
            $ganda = array_keys(array_filter($kode, fn ($n) => $n > 1));

            if ($ganda !== []) {
                throw new RuntimeException("Sheet \"{$s['kode_sheet']}\" memiliki kode komponen ganda (".implode(', ', $ganda).'). Perbaiki file master lalu unggah ulang.');
            }
        }
    }
}
