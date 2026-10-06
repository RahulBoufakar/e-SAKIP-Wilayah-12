<?php

use App\Services\RabMasterInspector;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

function inspektorXlsx(): string
{
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7735.951');
    $ws->getCell('A1')->setValue('x');
    $ws->getCell('B40')->setValue('y');
    $ws->mergeCells('A17:A18');

    $lain = $s->createSheet();
    $lain->setTitle('Lain');
    $lain->getCell('A1')->setValue('z');
    $lain->mergeCells('C5:D6'); // tidak boleh bocor ke sheet lain

    $path = tempnam(sys_get_temp_dir(), 'rab-insp-').'.xlsx';
    (new Xlsx($s))->save($path);

    return $path;
}

it('infoSheet mengembalikan jumlah baris dan merge HANYA milik sheet yang diminta', function () {
    $path = inspektorXlsx();

    try {
        $info = (new RabMasterInspector)->infoSheet($path, '7735.951');

        expect($info['total_baris'])->toBe(40)
            ->and($info['merges'])->toBe(['A17:A18']);
    } finally {
        @unlink($path);
    }
});

it('infoSheet melempar RuntimeException bila sheet tidak ada', function () {
    $path = inspektorXlsx();

    try {
        expect(fn () => (new RabMasterInspector)->infoSheet($path, 'TidakAda'))->toThrow(RuntimeException::class);
    } finally {
        @unlink($path);
    }
});

it('mergeTerpotong: null bila merge seluruhnya di dalam atau seluruhnya di luar rentang', function () {
    expect(RabMasterInspector::mergeTerpotong(['A17:A18'], 1, 18))->toBeNull()   // di dalam
        ->and(RabMasterInspector::mergeTerpotong(['A17:A18'], 19, 25))->toBeNull() // di luar
        ->and(RabMasterInspector::mergeTerpotong(['A17:A18'], 1, 30))->toBeNull()
        ->and(RabMasterInspector::mergeTerpotong([], 1, 18))->toBeNull();
});

it('mergeTerpotong: mengembalikan rentang merge yang terpotong batas akhir atau awal', function () {
    expect(RabMasterInspector::mergeTerpotong(['A17:A18'], 1, 17))->toBe('A17:A18')   // batas akhir memotong
        ->and(RabMasterInspector::mergeTerpotong(['A17:A18'], 18, 25))->toBe('A17:A18') // batas awal memotong
        ->and(RabMasterInspector::mergeTerpotong(['A1:B2', 'C17:D19'], 1, 18))->toBe('C17:D19');
});
