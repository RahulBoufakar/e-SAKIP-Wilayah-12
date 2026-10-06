<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Info ringan satu sheet master untuk validasi koreksi admin (03, aturan validasi no. 3 dan 4):
 * jumlah baris (tanpa memuat sel) dan daftar merge (hanya sheet itu yang dimuat).
 */
class RabMasterInspector
{
    /**
     * @return array{total_baris: int, merges: string[]}
     */
    public function infoSheet(string $path, string $judulSheet): array
    {
        $reader = IOFactory::createReader('Xlsx');

        $info = null;
        foreach ($reader->listWorksheetInfo($path) as $sheet) {
            if ($sheet['worksheetName'] === $judulSheet) {
                $info = $sheet;
            }
        }
        if ($info === null) {
            throw new RuntimeException("Sheet \"{$judulSheet}\" tidak ada di file master.");
        }

        $reader->setLoadSheetsOnly([$judulSheet]);
        $reader->setReadEmptyCells(false);
        $spreadsheet = $reader->load($path);

        try {
            return [
                'total_baris' => (int) $info['totalRows'],
                'merges' => array_values($spreadsheet->getSheetByName($judulSheet)->getMergeCells()),
            ];
        } finally {
            $spreadsheet->disconnectWorksheets();
        }
    }

    /**
     * Rentang merge pertama yang terpotong oleh batas awal atau akhir [awal, akhir]
     * (sebagian barisnya di dalam rentang, sebagian di luar). Null bila tidak ada.
     *
     * @param  string[]  $merges
     */
    public static function mergeTerpotong(array $merges, int $awal, int $akhir): ?string
    {
        foreach ($merges as $rentang) {
            [[, $baris1], [, $baris2]] = Coordinate::rangeBoundaries($rentang);

            $memotongAwal = $baris1 < $awal && $baris2 >= $awal;
            $memotongAkhir = $baris1 <= $akhir && $baris2 > $akhir;

            if ($memotongAwal || $memotongAkhir) {
                return $rentang;
            }
        }

        return null;
    }
}
