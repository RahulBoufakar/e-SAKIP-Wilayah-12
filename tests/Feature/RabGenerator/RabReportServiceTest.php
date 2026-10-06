<?php

use App\Services\FormulaRemapper;
use App\Services\RabAssembler;
use App\Services\RabMasterAnalyzer;
use App\Services\RabPreviewRenderer;
use App\Services\RabReportService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
 * Master uji FIKTIF. Deteksi: header 1-18 | RO 19-20 | 051 (21) -> sub A 22-32 | 052 (33) -> sub A 34-36
 * | footer 37-40 (area cetak A1:X40). Sub A di 051 = 3000, sub A di 052 = 700.
 */
function laporMasterXlsx(?Closure $ubah = null): string
{
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7735.951');

    $isi = [
        1 => ['A' => 'Kementerian Fiktif'],
        10 => ['E' => 'Anggaran', 'F' => '=X19'],
        19 => ['A' => '7735.EBB.951', 'B' => 'Layanan Fiktif', 'X' => '=X21+X33'],
        21 => ['A' => '051', 'B' => 'Komponen Satu', 'X' => '=X22'],
        22 => ['A' => 'A', 'B' => 'Sub A satu', 'X' => '=X23'],
        23 => ['A' => '521211', 'B' => 'Akun Satu', 'X' => '=X24'],
        24 => ['B' => '- Detail', 'I' => 2, 'L' => 3, 'O' => 500, 'X' => '=I24*L24*O24'],
        33 => ['A' => '052', 'B' => 'Komponen Dua', 'X' => '=X34'],
        34 => ['A' => 'A', 'B' => 'Sub A dua', 'X' => '=X35'],
        35 => ['A' => '521211', 'B' => 'Akun Dua', 'X' => '=X36'],
        36 => ['B' => '- Detail', 'I' => 1, 'L' => 1, 'O' => 700, 'X' => '=I36*L36*O36'],
        38 => ['B' => 'Mengetahui'],
    ];
    foreach ($isi as $no => $kolom) {
        foreach ($kolom as $huruf => $nilai) {
            $sel = $ws->getCell($huruf.$no);
            is_string($nilai) && ! str_starts_with($nilai, '=')
                ? $sel->setValueExplicit($nilai, DataType::TYPE_STRING)
                : $sel->setValue($nilai);
        }
    }
    $ws->getPageSetup()->setPrintArea('A1:X40');

    $lain = $s->createSheet();
    $lain->setTitle('7733.001');
    $lain->getCell('A19')->setValueExplicit('7733.EBB.001', DataType::TYPE_STRING);
    $lain->getCell('B19')->setValueExplicit('RO Lain', DataType::TYPE_STRING);
    $lain->getCell('A21')->setValueExplicit('051', DataType::TYPE_STRING);
    $lain->getCell('A22')->setValueExplicit('A', DataType::TYPE_STRING);
    $lain->getCell('A23')->setValueExplicit('521211', DataType::TYPE_STRING);
    $lain->getCell('B24')->setValueExplicit('- Detail', DataType::TYPE_STRING);
    $lain->getPageSetup()->setPrintArea('A1:X26');

    if ($ubah) {
        $ubah($s);
    }

    $path = tempnam(sys_get_temp_dir(), 'rab-lapor-').'.xlsx';
    (new Xlsx($s))->save($path);

    return $path;
}

/** @param array<string, int[]> $kelompok kode_sheet => baris_awal kelompok terpilih */
function laporPilihan(string $path, array $kelompok, bool $header = true, bool $footer = true): array
{
    $pilihan = [];
    foreach ((new RabMasterAnalyzer)->analyze($path)['sheets'] as $sheet) {
        if (isset($kelompok[$sheet['kode_sheet']])) {
            $pilihan[] = ['peta' => $sheet, 'header' => $header, 'footer' => $footer, 'kelompok_baris' => $kelompok[$sheet['kode_sheet']]];
        }
    }

    return $pilihan;
}

function laporService(): RabReportService
{
    return new RabReportService(new RabAssembler(new FormulaRemapper));
}

it('bangunDariFile merakit hanya sheet terpilih dengan total yang benar', function () {
    $path = laporMasterXlsx();

    try {
        $hasil = laporService()->bangunDariFile($path, laporPilihan($path, ['7735.951' => [34]]));
        $s = $hasil['spreadsheet'];

        expect($s->getSheetCount())->toBe(1)
            ->and($s->getSheet(0)->getTitle())->toBe('7735.951')
            ->and($s->getSheet(0)->getCell('X19')->getValue())->toBe('=X21')   // suku komponen 051 dibuang
            ->and((float) $s->getSheet(0)->getCell('X19')->getCalculatedValue())->toEqual(700)
            ->and((float) $s->getSheet(0)->getCell('F10')->getCalculatedValue())->toEqual(700)
            ->and($hasil['laporan']['unmapped_formulas'])->toBe([]);
    } finally {
        @unlink($path);
    }
});

it('bangunDariFile memuat sheet lain bila terpilih (dua sheet hasil, urutan master)', function () {
    $path = laporMasterXlsx();

    try {
        $s = laporService()->bangunDariFile($path, laporPilihan($path, ['7733.001' => [22], '7735.951' => [22]]))['spreadsheet'];

        expect(array_map(fn ($w) => $w->getTitle(), $s->getAllSheets()))->toBe(['7735.951', '7733.001']);
    } finally {
        @unlink($path);
    }
});

it('periksaLaporan meloloskan laporan bersih dan hanya merge terlewat', function () {
    $bersih = ['merges_skipped' => 0, 'unmapped_formulas' => [], 'cross_sheet_formulas' => []];

    laporService()->periksaLaporan($bersih);
    laporService()->periksaLaporan(['merges_skipped' => 3] + $bersih);

    expect(true)->toBeTrue();
});

it('periksaLaporan menolak rumus tak terpetakan atau lintas-sheet', function () {
    $dasar = ['merges_skipped' => 0, 'unmapped_formulas' => [], 'cross_sheet_formulas' => []];

    expect(fn () => laporService()->periksaLaporan(['unmapped_formulas' => ['7735.951!G10: X34']] + $dasar))
        ->toThrow(RuntimeException::class)
        ->and(fn () => laporService()->periksaLaporan(['cross_sheet_formulas' => ['7735.951!G10']] + $dasar))
        ->toThrow(RuntimeException::class);
});

it('rumus yang merujuk bagian tak terpilih menghasilkan laporan yang ditolak periksaLaporan', function () {
    $path = laporMasterXlsx(fn (Spreadsheet $s) => $s->getSheetByName('7735.951')->getCell('G10')->setValue('=X34'));

    try {
        $hasil = laporService()->bangunDariFile($path, laporPilihan($path, ['7735.951' => [22]])); // 052 tidak dipilih

        expect($hasil['laporan']['unmapped_formulas'])->toBe(['7735.951!G10: X34'])
            ->and(fn () => laporService()->periksaLaporan($hasil['laporan']))->toThrow(RuntimeException::class);
    } finally {
        @unlink($path);
    }
});

it('simpanXlsx membuat folder yang belum ada dan hasilnya bisa dibuka ulang dengan nilai benar', function () {
    $path = laporMasterXlsx();
    $folder = sys_get_temp_dir().'/rab-simpan-'.uniqid();
    $tujuan = "{$folder}/temp/hasil.xlsx";

    try {
        $s = laporService()->bangunDariFile($path, laporPilihan($path, ['7735.951' => [22]]))['spreadsheet'];
        laporService()->simpanXlsx($s, $tujuan);

        $buka = IOFactory::createReader('Xlsx')->load($tujuan);

        expect(is_file($tujuan))->toBeTrue()
            ->and($buka->getSheetCount())->toBe(1)
            ->and((float) $buka->getSheet(0)->getCell('X19')->getCalculatedValue())->toEqual(3000);
    } finally {
        @unlink($path);
        @unlink($tujuan);
        @rmdir("{$folder}/temp");
        @rmdir($folder);
    }
});

it('RabPreviewRenderer menghasilkan satu dokumen HTML per sheet, hanya berisi bagian terpilih', function () {
    $path = laporMasterXlsx();

    try {
        $s = laporService()->bangunDariFile($path, laporPilihan($path, ['7735.951' => [22], '7733.001' => [22]]))['spreadsheet'];
        $previews = (new RabPreviewRenderer)->render($s);

        expect($previews)->toHaveCount(2)
            ->and($previews[0]['judul'])->toBe('7735.951')
            ->and($previews[1]['judul'])->toBe('7733.001')
            ->and($previews[0]['html'])->toContain('Layanan Fiktif')->toContain('Komponen Satu')->toContain('Mengetahui')
            ->and($previews[0]['html'])->not->toContain('Komponen Dua')
            ->and($previews[1]['html'])->toContain('RO Lain')->not->toContain('Layanan Fiktif');
    } finally {
        @unlink($path);
    }
});
