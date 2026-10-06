<?php

namespace App\Services;

use App\Models\Sheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;

/**
 * RAB Generator 02-arsitektur.md, alur preview — langkah 2 sampai 4:
 * memuat master (hanya sheet terpilih), merakit, dan menyimpan berkas hasil.
 * Validasi pilihan, cache token, dan otorisasi ada di controller.
 */
class RabReportService
{
    public function __construct(private RabAssembler $assembler)
    {
    }

    /**
     * Peta untuk RabAssembler dari data di database (termasuk koreksi admin), bukan dari
     * analisis ulang file. Semua nomor baris di-cast int: assembler membandingkannya secara ketat.
     */
    public function petaDariSheet(Sheet $sheet): array
    {
        $sheet->loadMissing([
            'header', 'footer',
            'kategori' => fn ($q) => $q->orderBy('urutan'),
            'kategori.kelompok' => fn ($q) => $q->orderBy('urutan'),
        ]);

        $rentang = fn ($blok) => $blok ? ['baris_awal' => (int) $blok->baris_awal, 'baris_akhir' => (int) $blok->baris_akhir] : null;

        return [
            'kode_sheet' => $sheet->kode_sheet,
            'urutan' => (int) $sheet->urutan,
            'ro_baris_awal' => (int) $sheet->ro_baris_awal,
            'ro_baris_akhir' => (int) $sheet->ro_baris_akhir,
            'kolom_akhir' => $sheet->kolom_akhir,
            'header' => $rentang($sheet->header),
            'footer' => $rentang($sheet->footer),
            'kategori' => $sheet->kategori->map(fn ($k) => [
                'baris_awal' => (int) $k->baris_awal,
                'baris_akhir' => (int) $k->baris_akhir,
                'kelompok' => $k->kelompok->map(fn ($g) => [
                    'baris_awal' => (int) $g->baris_awal,
                    'baris_akhir' => (int) $g->baris_akhir,
                ])->all(),
            ])->all(),
        ];
    }

    /**
     * Muat HANYA sheet terpilih (setLoadSheetsOnly menurunkan memori 4-5x di PhpSpreadsheet 1.30.x)
     * lalu rakit. Sheet master dibuang oleh assembler; yang tersisa hanya sheet hasil.
     *
     * @param  array<int, array{peta: array, header: bool, footer: bool, kelompok_baris: int[]}>  $pilihan
     * @return array{spreadsheet: Spreadsheet, laporan: array}
     */
    public function bangunDariFile(string $path, array $pilihan): array
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setLoadSheetsOnly(array_map(fn ($p) => $p['peta']['kode_sheet'], $pilihan));

        return $this->assembler->build($reader->load($path), $pilihan);
    }

    /**
     * Hasil dengan rumus tak terpetakan atau rumus lintas-sheet TIDAK boleh diberikan ke user:
     * totalnya bisa salah tanpa error (01 D1). Merge yang terlewat hanya soal tampilan, jadi diloloskan.
     *
     * @throws RuntimeException
     */
    public function periksaLaporan(array $laporan): void
    {
        if ($laporan['unmapped_formulas'] !== [] || $laporan['cross_sheet_formulas'] !== []) {
            throw new RuntimeException('Template tidak dapat dirakit untuk pilihan ini karena ada rumus yang merujuk bagian yang tidak dipilih. Hubungi Administrator.');
        }
    }

    /** Simpan ke path absolut (membuat folder bila perlu). Rumus dihitung dulu agar nilainya ikut tersimpan. */
    public function simpanXlsx(Spreadsheet $spreadsheet, string $pathAbsolut): void
    {
        $folder = dirname($pathAbsolut);
        if (! is_dir($folder)) {
            mkdir($folder, 0775, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->setPreCalculateFormulas(true); // default; dipertahankan eksplisit (01: jangan dimatikan)
        $writer->save($pathAbsolut);
    }
}
