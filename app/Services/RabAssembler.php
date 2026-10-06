<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Calculation\Calculation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * RAB Generator 05-algoritma-perakit.md §3-5 — merakit workbook hasil.
 *
 * Satu sheet master terpilih => SATU sheet hasil (header & footer miliknya
 * sendiri). Hasil DIRAKIT ke sheet baru dari potongan baris master; master
 * tidak pernah dipotong di tempat (removeRow/sembunyikan baris membuat total
 * salah tanpa error, lihat 01 D1).
 *
 * Perakitan terjadi di dalam satu workbook yang sama supaya indeks gaya (XF)
 * bisa disalin langsung. Semua sheet yang bukan hasil dibuang di akhir, jadi
 * pemanggil sebaiknya memuat master dengan setLoadSheetsOnly(sheet terpilih).
 *
 * Input `peta` adalah satu elemen `sheets` dari RabMasterAnalyzer. Kelompok
 * dipilih lewat `baris_awal`-nya (unik per sheet; kode kelompok TIDAK unik).
 */
class RabAssembler
{
    public function __construct(private FormulaRemapper $remapper)
    {
    }

    /**
     * @param  array<int, array{peta: array, header: bool, footer: bool, kelompok_baris: int[]}>  $pilihan
     * @return array{spreadsheet: Spreadsheet, laporan: array{merges_skipped: int, unmapped_formulas: string[], cross_sheet_formulas: string[]}}
     */
    public function build(Spreadsheet $spreadsheet, array $pilihan): array
    {
        if ($pilihan === []) {
            throw new InvalidArgumentException('Pilih minimal satu sheet.');
        }

        usort($pilihan, fn ($a, $b) => $a['peta']['urutan'] <=> $b['peta']['urutan']);

        $laporan = ['merges_skipped' => 0, 'unmapped_formulas' => [], 'cross_sheet_formulas' => []];
        $hasil = []; // [Worksheet hasil, judul akhir]

        foreach ($pilihan as $n => $item) {
            $peta = $item['peta'];
            $sumber = $spreadsheet->getSheetByName($peta['kode_sheet']);

            if ($sumber === null) {
                throw new InvalidArgumentException("Sheet \"{$peta['kode_sheet']}\" tidak ada di file master.");
            }

            $potongan = $this->susunPotongan($peta, $item['header'], $item['footer'], $item['kelompok_baris']);

            // Judul sementara: judul akhir sama dengan judul sheet master yang masih ada.
            $tujuan = $spreadsheet->createSheet();
            $tujuan->setTitle('__rab_'.($n + 1), false);

            $this->rakit($sumber, $tujuan, $potongan, $peta, $laporan);

            $hasil[] = [$tujuan, $peta['kode_sheet']];
        }

        $this->buangSelainHasil($spreadsheet, array_column($hasil, 0));

        foreach ($hasil as [$tujuan, $judul]) {
            $tujuan->setTitle($judul, false);
        }

        // Cache hitung dikunci per judul sheet; judul master yang sudah dibuang
        // dipakai ulang oleh sheet hasil, jadi buang cache supaya tidak basi.
        Calculation::getInstance($spreadsheet)->clearCalculationCache();
        $spreadsheet->setActiveSheetIndex(0);

        return ['spreadsheet' => $spreadsheet, 'laporan' => $laporan];
    }

    /**
     * Urutan keluaran = urutan di master (05 §3): header, RO, lalu per komponen yang
     * punya kelompok terpilih: segmen komponen (sekali) + kelompok terpilih, lalu footer.
     *
     * @return array<int, array{0: int, 1: int}> daftar [baris awal, baris akhir] di master
     */
    private function susunPotongan(array $peta, bool $header, bool $footer, array $kelompokBaris): array
    {
        if ($kelompokBaris === []) {
            throw new InvalidArgumentException("Sheet \"{$peta['kode_sheet']}\" terpilih tanpa kelompok.");
        }

        $semua = [];
        foreach ($peta['kategori'] as $kategori) {
            foreach ($kategori['kelompok'] as $kelompok) {
                $semua[] = $kelompok['baris_awal'];
            }
        }
        if (array_diff($kelompokBaris, $semua) !== []) {
            throw new InvalidArgumentException("Ada kelompok yang bukan milik sheet \"{$peta['kode_sheet']}\".");
        }

        $potongan = [];
        if ($header && $peta['header']) {
            $potongan[] = [$peta['header']['baris_awal'], $peta['header']['baris_akhir']];
        }
        $potongan[] = [$peta['ro_baris_awal'], $peta['ro_baris_akhir']];

        foreach ($peta['kategori'] as $kategori) {
            $dipilih = array_filter($kategori['kelompok'], fn ($k) => in_array($k['baris_awal'], $kelompokBaris, true));
            if ($dipilih === []) {
                continue;
            }

            $potongan[] = [$kategori['baris_awal'], $kategori['baris_akhir']];
            foreach ($dipilih as $kelompok) {
                $potongan[] = [$kelompok['baris_awal'], $kelompok['baris_akhir']];
            }
        }

        if ($footer && $peta['footer']) {
            $potongan[] = [$peta['footer']['baris_awal'], $peta['footer']['baris_akhir']];
        }

        return $potongan;
    }

    private function rakit(Worksheet $sumber, Worksheet $tujuan, array $potongan, array $peta, array &$laporan): void
    {
        $kolomAkhir = Coordinate::columnIndexFromString($peta['kolom_akhir']);

        // Peta baris lama -> baru dibangun SEBELUM menyalin (rumus ditulis ulang saat menyalin).
        $rowMap = [];
        $potonganBaris = []; // baris lama => indeks potongan
        $baru = 1;
        foreach ($potongan as $i => [$awal, $akhir]) {
            for ($r = $awal; $r <= $akhir; $r++) {
                $rowMap[$r] = $baru++;
                $potonganBaris[$r] = $i;
            }
        }
        $barisTerakhir = $baru - 1;

        foreach ($rowMap as $lama => $baruRow) {
            $this->salinBaris($sumber, $tujuan, $lama, $baruRow, $kolomAkhir, $rowMap, $peta['kode_sheet'], $laporan);
        }

        $this->salinMerge($sumber, $tujuan, $rowMap, $potonganBaris, $kolomAkhir, $laporan);
        $this->salinKolom($sumber, $tujuan, $kolomAkhir);
        $this->salinSetelanHalaman($sumber, $tujuan, $rowMap, $peta['kolom_akhir'], $barisTerakhir);
    }

    private function salinBaris(Worksheet $sumber, Worksheet $tujuan, int $lama, int $baru, int $kolomAkhir, array $rowMap, string $kodeSheet, array &$laporan): void
    {
        for ($c = 1; $c <= $kolomAkhir; $c++) {
            if (! $sumber->cellExists([$c, $lama])) {
                continue;
            }

            $sel = $sumber->getCell([$c, $lama]);
            $nilai = $sel->getValue();
            $tipe = $sel->getDataType();

            if ($tipe === 'f' && is_string($nilai)) {
                $remap = $this->remapper->remap($nilai, $rowMap);
                $nilai = $remap['formula'];
                $koordinat = Coordinate::stringFromColumnIndex($c).$lama;

                foreach ($remap['tidak_terpetakan'] as $ref) {
                    $laporan['unmapped_formulas'][] = "{$kodeSheet}!{$koordinat}: {$ref}";
                }
                if ($remap['lintas_sheet']) {
                    $laporan['cross_sheet_formulas'][] = "{$kodeSheet}!{$koordinat}";
                }
            }

            $tujuan->getCell([$c, $baru])
                ->setValueExplicit($nilai, $tipe)
                ->setXfIndex($sel->getXfIndex());
        }

        $dimensi = $sumber->getRowDimensions()[$lama] ?? null; // getRowDimension() membuat dimensi baru di sumber
        if ($dimensi !== null) {
            $tujuanDimensi = $tujuan->getRowDimension($baru);
            $tujuanDimensi->setRowHeight($dimensi->getRowHeight());
            $tujuanDimensi->setVisible($dimensi->getVisible());
            if ($dimensi->getXfIndex() !== null) {
                $tujuanDimensi->setXfIndex($dimensi->getXfIndex());
            }
        }
    }

    /**
     * Merge disalin hanya bila seluruhnya berada dalam SATU potongan dan di dalam kolom
     * yang disalin. Merge yang menyentuh baris terpilih tapi tidak memenuhi syarat itu
     * dihitung `merges_skipped` (harus 0, T8).
     */
    private function salinMerge(Worksheet $sumber, Worksheet $tujuan, array $rowMap, array $potonganBaris, int $kolomAkhir, array &$laporan): void
    {
        foreach ($sumber->getMergeCells() as $rentang) {
            [$awal, $akhir] = Coordinate::rangeBoundaries($rentang);
            [$kolom1, $baris1] = $awal;
            [$kolom2, $baris2] = $akhir;

            $baris = range($baris1, $baris2);
            $terpilih = array_filter($baris, fn ($r) => isset($rowMap[$r]));

            if ($terpilih === [] || $kolom1 > $kolomAkhir) {
                continue; // di luar bagian yang disalin: bukan merge yang "terlewat"
            }

            $satuPotongan = count($terpilih) === count($baris)
                && count(array_unique(array_map(fn ($r) => $potonganBaris[$r], $baris))) === 1;

            if (! $satuPotongan || $kolom2 > $kolomAkhir) {
                $laporan['merges_skipped']++;

                continue;
            }

            $tujuan->mergeCells(
                Coordinate::stringFromColumnIndex($kolom1).$rowMap[$baris1].':'.Coordinate::stringFromColumnIndex($kolom2).$rowMap[$baris2],
                Worksheet::MERGE_CELL_CONTENT_HIDE
            );
        }
    }

    /** Lebar, visibilitas, dan gaya kolom — hanya kolom area cetak (D9). */
    private function salinKolom(Worksheet $sumber, Worksheet $tujuan, int $kolomAkhir): void
    {
        foreach ($sumber->getColumnDimensions() as $huruf => $dimensi) {
            if (Coordinate::columnIndexFromString($huruf) > $kolomAkhir) {
                continue;
            }

            $tujuanDimensi = $tujuan->getColumnDimension($huruf);
            $tujuanDimensi->setWidth($dimensi->getWidth());
            $tujuanDimensi->setVisible($dimensi->getVisible());
            $tujuanDimensi->setAutoSize($dimensi->getAutoSize());
            if ($dimensi->getXfIndex() !== null) {
                $tujuanDimensi->setXfIndex($dimensi->getXfIndex());
            }
        }

        $tujuan->getDefaultColumnDimension()->setWidth($sumber->getDefaultColumnDimension()->getWidth());
        $tujuan->getDefaultRowDimension()->setRowHeight($sumber->getDefaultRowDimension()->getRowHeight());
    }

    private function salinSetelanHalaman(Worksheet $sumber, Worksheet $tujuan, array $rowMap, string $kolomAkhir, int $barisTerakhir): void
    {
        $tujuan->setPageSetup(clone $sumber->getPageSetup());
        $tujuan->setPageMargins(clone $sumber->getPageMargins());
        $tujuan->setHeaderFooter(clone $sumber->getHeaderFooter());
        $tujuan->setShowGridlines($sumber->getShowGridlines());
        $tujuan->setPrintGridlines($sumber->getPrintGridlines());

        $setelan = $tujuan->getPageSetup();
        $setelan->setPrintArea("A1:{$kolomAkhir}{$barisTerakhir}");

        // Baris judul berulang merujuk nomor baris master: petakan, atau lepas bila tidak ikut.
        if ($setelan->isRowsToRepeatAtTopSet()) {
            [$dari, $sampai] = $setelan->getRowsToRepeatAtTop();

            if (isset($rowMap[$dari], $rowMap[$sampai])) {
                $setelan->setRowsToRepeatAtTopByStartAndEnd($rowMap[$dari], $rowMap[$sampai]);
            } else {
                $setelan->setRowsToRepeatAtTop([0, 0]);
            }
        }
    }

    private function buangSelainHasil(Spreadsheet $spreadsheet, array $hasil): void
    {
        for ($i = $spreadsheet->getSheetCount() - 1; $i >= 0; $i--) {
            if (! in_array($spreadsheet->getSheet($i), $hasil, true)) {
                $spreadsheet->removeSheetByIndex($i);
            }
        }
    }
}
