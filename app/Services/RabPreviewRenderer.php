<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Html;

/**
 * RAB Generator 02-arsitektur.md — workbook hasil ke HTML pratinjau, satu dokumen per sheet
 * (tab per sheet di modal). Writer\Html bukan Excel; pratinjau hanyalah perkiraan.
 *
 * Perbaikan tampilan (langkah 10): format jam pada sel string, garis kisi, dan nowrap.
 * PERHATIAN: render() MENGUBAH workbook yang diberikan (format sel string dan garis kisi).
 * Panggil hanya pada workbook khusus pratinjau, setelah berkas unduhan disimpan.
 * Jangan pakai PhpSpreadsheet 5.x untuk Writer\Html (10-15x lebih lambat).
 */
class RabPreviewRenderer
{
    /**
     * @return array<int, array{judul: string, html: string}>
     */
    public function render(Spreadsheet $spreadsheet): array
    {
        $this->perbaikiWorkbook($spreadsheet);
        $cssNowrap = $this->cssNowrap($spreadsheet);

        $hasil = [];

        foreach ($spreadsheet->getAllSheets() as $i => $sheet) {
            $writer = new Html($spreadsheet);
            $writer->setSheetIndex($i);
            $writer->setGenerateSheetNavigationBlock(false);

            $html = $writer->generateHtmlAll();
            // Sisipkan di akhir blok <style> milik writer supaya mengalahkan aturan bawaannya.
            $html = preg_replace('#</style>#', $cssNowrap.'</style>', $html, 1);

            $hasil[] = ['judul' => $sheet->getTitle(), 'html' => $html];
        }

        return $hasil;
    }

    private function perbaikiWorkbook(Spreadsheet $spreadsheet): void
    {
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->setShowGridlines(false);

            foreach ($sheet->getCoordinates(false) as $koordinat) {
                $sel = $sheet->getCell($koordinat);

                // Teks seperti "1." atau "2.3" yang berformat jam di master tampil "0:00" di HTML.
                // Hanya sel string yang diubah; angka dan tanggal sungguhan tidak disentuh.
                if ($sel->getDataType() === DataType::TYPE_STRING
                    && $sheet->getStyle($koordinat)->getNumberFormat()->getFormatCode() !== NumberFormat::FORMAT_GENERAL) {
                    $sheet->getStyle($koordinat)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_GENERAL);
                }
            }
        }
    }

    /**
     * Excel membiarkan teks non-wrap meluber ke sel kosong di kanannya; HTML tidak, sehingga teks
     * membungkus. Semua sel dibuat nowrap, kecuali gaya yang memang diatur wrap di Excel.
     */
    private function cssNowrap(Spreadsheet $spreadsheet): string
    {
        $css = "      table td, table th { white-space: nowrap; }\n";

        foreach ($spreadsheet->getCellXfCollection() as $indeks => $gaya) {
            if ($gaya->getAlignment()->getWrapText()) {
                $css .= "      td.style{$indeks}, th.style{$indeks} { white-space: normal; }\n";
            }
        }

        return $css;
    }
}
