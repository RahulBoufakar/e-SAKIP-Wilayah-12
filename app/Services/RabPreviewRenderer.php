<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Html;

/**
 * RAB Generator 02-arsitektur.md — workbook hasil ke HTML pratinjau, satu dokumen per sheet
 * (tab per sheet di modal). Writer\Html bukan Excel; pratinjau hanyalah perkiraan.
 *
 * Perbaikan tampilan (format jam salah, garis kisi, nowrap) adalah langkah 10 dan sengaja
 * belum ada di sini. Jangan pakai PhpSpreadsheet 5.x untuk Writer\Html (10-15x lebih lambat).
 */
class RabPreviewRenderer
{
    /**
     * @return array<int, array{judul: string, html: string}>
     */
    public function render(Spreadsheet $spreadsheet): array
    {
        $hasil = [];

        foreach ($spreadsheet->getAllSheets() as $i => $sheet) {
            $writer = new Html($spreadsheet);
            $writer->setSheetIndex($i);
            $writer->setGenerateSheetNavigationBlock(false);

            $hasil[] = ['judul' => $sheet->getTitle(), 'html' => $writer->generateHtmlAll()];
        }

        return $hasil;
    }
}
