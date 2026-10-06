<?php

use App\Services\FormulaRemapper;
use App\Services\RabAssembler;
use App\Services\RabMasterAnalyzer;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/*
 * Master uji FIKTIF (06: jangan memasukkan master asli ke repositori), dibangun dari kode.
 * Kolom I/L/O = volume/satuan/harga, X = jumlah. Rumus induk berupa rantai "+" sel eksplisit
 * (bukan SUM rentang), akun memakai SUM rentang detail. Kolom Z = kolom bantu di luar area cetak.
 */

/** @param array<int, array<string, mixed>> $baris [nomor baris => [kolom => nilai]] */
function masterRabTulis(Worksheet $ws, array $baris): void
{
    foreach ($baris as $no => $kolom) {
        foreach ($kolom as $huruf => $nilai) {
            $sel = $ws->getCell($huruf.$no);
            is_string($nilai) && ! str_starts_with($nilai, '=')
                ? $sel->setValueExplicit($nilai, DataType::TYPE_STRING)
                : $sel->setValue($nilai);
        }
    }
}

function masterRabBarisBesar(): array
{
    return [
        1 => ['A' => 'Kementerian Fiktif'], // di dalam merge A1:H1 (mergeCells() mengosongkan sel lain)
        10 => ['E' => 'Anggaran', 'F' => '=X19'],
        17 => ['A' => 'KODE', 'B' => 'URAIAN', 'I' => 'VOL', 'L' => 'HARGA', 'X' => 'JUMLAH'],
        19 => ['A' => '7735.EBB.951', 'B' => 'Layanan Fiktif', 'X' => '=X21+X33', 'Z' => '=X19*0.9'],
        21 => ['A' => '051', 'B' => 'Komponen Satu', 'X' => '=X22+X28'],
        22 => ['A' => 'A', 'B' => 'Sub A satu', 'X' => '=X23+X26'],
        23 => ['A' => '521211', 'B' => 'Belanja Honor', 'X' => '=SUM(X24:X25)'],
        24 => ['B' => '- Honor narasumber', 'I' => 2, 'L' => 3, 'O' => 500, 'X' => '=I24*L24*O24', 'Z' => 'catatan internal'],
        25 => ['B' => '- Honor moderator', 'I' => 1, 'L' => 1, 'O' => 1000, 'X' => '=I25*L25*O25'],
        26 => ['A' => '521213', 'B' => 'Belanja Perjalanan', 'X' => '=X27'],
        27 => ['B' => '- Tiket', 'I' => 1, 'L' => 1, 'O' => 2000, 'X' => '=I27*L27*O27'],
        28 => ['A' => 'B', 'B' => 'Sub B satu', 'X' => '=X29'],
        29 => ['A' => '524111', 'B' => 'Belanja Dinas', 'X' => '=SUM(X30:X31)'],
        30 => ['B' => '- Transport', 'I' => 3, 'L' => 1, 'O' => 100, 'X' => '=I30*L30*O30'],
        31 => ['B' => '- Penginapan', 'I' => 2, 'L' => 1, 'O' => 150, 'X' => '=I31*L31*O31'],
        33 => ['A' => '052', 'B' => 'Komponen Dua', 'X' => '=X34'],
        34 => ['A' => 'A', 'B' => 'Sub A dua', 'X' => '=X35'],
        35 => ['A' => '521211', 'B' => 'Belanja Honor', 'X' => '=X36'],
        36 => ['B' => '- Honor', 'I' => 1, 'L' => 1, 'O' => 500, 'X' => '=I36*L36*O36'],
        38 => ['B' => 'Mengetahui'],
        41 => ['B' => 'Pejabat Fiktif'],
    ];
}

function masterRabBarisKecil(string $roKode, string $nama, int $harga): array
{
    return [
        1 => ['A' => 'KOP', 'B' => "Header {$nama}"],
        10 => ['E' => 'Anggaran', 'F' => '=X19'],
        19 => ['A' => $roKode, 'B' => $nama, 'X' => '=X21'],
        21 => ['A' => '051', 'B' => 'Komponen', 'X' => '=X22'],
        22 => ['A' => 'A', 'B' => 'Sub A', 'X' => '=X23'],
        23 => ['A' => '521211', 'B' => 'Akun', 'X' => '=X24'],
        24 => ['B' => '- Detail', 'I' => 1, 'L' => 1, 'O' => $harga, 'X' => '=I24*L24*O24'],
        26 => ['B' => "Mengetahui {$nama}"],
    ];
}

function masterRab(): Spreadsheet
{
    $s = new Spreadsheet;

    $besar = $s->getActiveSheet();
    $besar->setTitle('7735.951');
    masterRabTulis($besar, masterRabBarisBesar());
    $besar->getPageSetup()->setPrintArea('A1:X42');
    $besar->mergeCells('A1:H1');                                   // merge di dalam header
    $besar->getRowDimension(22)->setRowHeight(30);
    $besar->getColumnDimension('B')->setWidth(40);
    $besar->getColumnDimension('C')->setVisible(false);
    $besar->getStyle('B19')->getFont()->setBold(true);
    $besar->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd(17, 17);

    foreach ([['7733.001', '7733.EBB.001', 'RO Dua', 1000], ['7731.002', '7731.EBB.002', 'RO Tiga', 2000]] as [$judul, $ro, $nama, $harga]) {
        $kecil = $s->createSheet();
        $kecil->setTitle($judul);
        masterRabTulis($kecil, masterRabBarisKecil($ro, $nama, $harga));
        $kecil->getPageSetup()->setPrintArea('A1:X28');
    }

    return $s;
}

/**
 * @param  array<string, int[]|null>  $kelompok  kode_sheet => baris_awal kelompok (null = semua)
 */
function pilihanRab(array $peta, array $kelompok, bool $header = true, bool $footer = true): array
{
    $pilihan = [];
    foreach ($peta['sheets'] as $sheet) {
        if (! array_key_exists($sheet['kode_sheet'], $kelompok)) {
            continue;
        }

        $semua = [];
        foreach ($sheet['kategori'] as $kategori) {
            foreach ($kategori['kelompok'] as $k) {
                $semua[] = $k['baris_awal'];
            }
        }

        $pilihan[] = ['peta' => $sheet, 'header' => $header, 'footer' => $footer, 'kelompok_baris' => $kelompok[$sheet['kode_sheet']] ?? $semua];
    }

    return $pilihan;
}

function rakitRab(Spreadsheet $master, array $kelompok, bool $header = true, bool $footer = true): array
{
    $peta = (new RabMasterAnalyzer)->analyzeSpreadsheet($master);

    return (new RabAssembler(new FormulaRemapper))->build($master, pilihanRab($peta, $kelompok, $header, $footer));
}

/** Jumlah detail (kolom A kosong, B diawali "-") pada rentang kelompok terpilih, dihitung dari MASTER. */
function jumlahDetailRab(Spreadsheet $master, string $judul, ?array $kelompokBaris = null): float
{
    $peta = array_values(array_filter(
        (new RabMasterAnalyzer)->analyzeSpreadsheet($master)['sheets'],
        fn ($sheet) => $sheet['kode_sheet'] === $judul
    ))[0];
    $ws = $master->getSheetByName($judul);
    $total = 0.0;

    foreach ($peta['kategori'] as $kategori) {
        foreach ($kategori['kelompok'] as $k) {
            if ($kelompokBaris !== null && ! in_array($k['baris_awal'], $kelompokBaris, true)) {
                continue;
            }
            for ($r = $k['baris_awal']; $r <= $k['baris_akhir']; $r++) {
                $a = trim((string) $ws->getCell("A{$r}")->getValue());
                $b = trim((string) $ws->getCell("B{$r}")->getValue());
                if ($a === '' && str_starts_with($b, '-')) {
                    $total += (float) $ws->getCell("X{$r}")->getCalculatedValue();
                }
            }
        }
    }

    return $total;
}

function cariBarisRab(Worksheet $ws, string $nilai, string $kolom = 'A'): ?int
{
    for ($r = 1; $r <= $ws->getHighestDataRow(); $r++) {
        if (trim((string) $ws->getCell("{$kolom}{$r}")->getValue()) === $nilai) {
            return $r;
        }
    }

    return null;
}

function totalRoRab(Spreadsheet $s, string $judul, string $kodeRo): float
{
    $ws = $s->getSheetByName($judul);

    return (float) $ws->getCell('X'.cariBarisRab($ws, $kodeRo))->getCalculatedValue();
}

// --- T1/T2: total RO dan sel Anggaran mengikuti isi terpilih ---

it('T1: satu kelompok dari sheet besar — total RO = jumlah detail terpilih, Anggaran header ikut, komponen lain hilang', function () {
    $master = masterRab();
    $diharapkan = jumlahDetailRab($master, '7735.951', [22]); // dihitung dari master, bukan hardcode

    $hasil = rakitRab($master, ['7735.951' => [22]]);
    $out = $hasil['spreadsheet']->getSheetByName('7735.951');

    expect(totalRoRab($hasil['spreadsheet'], '7735.951', '7735.EBB.951'))->toEqual($diharapkan)
        ->and((float) $out->getCell('F10')->getCalculatedValue())->toEqual($diharapkan)
        ->and(cariBarisRab($out, '051'))->not->toBeNull()
        ->and(cariBarisRab($out, '052'))->toBeNull()
        ->and(cariBarisRab($out, 'B'))->toBeNull()
        // teks rumus ikut ditulis ulang: suku yang barisnya tidak ikut (X33, X28) dibuang
        ->and($out->getCell('X19')->getValue())->toBe('=X21')
        ->and($out->getCell('X21')->getValue())->toBe('=X22');
});

it('T2: satu kelompok utuh dari komponen lain — komponen dan RO terlihat, komponen pertama tidak', function () {
    $master = masterRab();
    $diharapkan = jumlahDetailRab($master, '7735.951', [34]);

    $hasil = rakitRab($master, ['7735.951' => [34]]);
    $out = $hasil['spreadsheet']->getSheetByName('7735.951');

    expect(totalRoRab($hasil['spreadsheet'], '7735.951', '7735.EBB.951'))->toEqual($diharapkan)
        ->and(cariBarisRab($out, '052'))->not->toBeNull()
        ->and(cariBarisRab($out, '051'))->toBeNull();
});

// --- T3/T4: banyak sheet ---

it('T3: tiga sheet sebagian kelompok — tiap sheet punya header, footer, dan total sendiri', function () {
    $master = masterRab();
    $e1 = jumlahDetailRab($master, '7735.951', [34]);
    $e2 = jumlahDetailRab($master, '7733.001');
    $e3 = jumlahDetailRab($master, '7731.002');

    $s = rakitRab($master, ['7735.951' => [34], '7733.001' => null, '7731.002' => null])['spreadsheet'];

    expect($s->getSheetCount())->toBe(3)
        ->and(array_map(fn ($w) => $w->getTitle(), $s->getAllSheets()))->toBe(['7735.951', '7733.001', '7731.002'])
        ->and($s->getSheetByName('7735.951')->getCell('A1')->getValue())->toBe('Kementerian Fiktif')
        ->and($s->getSheetByName('7733.001')->getCell('B1')->getValue())->toBe('Header RO Dua')
        ->and($s->getSheetByName('7731.002')->getCell('B1')->getValue())->toBe('Header RO Tiga')
        ->and(cariBarisRab($s->getSheetByName('7733.001'), 'Mengetahui RO Dua', 'B'))->not->toBeNull()
        ->and(cariBarisRab($s->getSheetByName('7731.002'), 'Mengetahui RO Dua', 'B'))->toBeNull()
        ->and(totalRoRab($s, '7735.951', '7735.EBB.951'))->toEqual($e1)
        ->and(totalRoRab($s, '7733.001', '7733.EBB.001'))->toEqual($e2)
        ->and(totalRoRab($s, '7731.002', '7731.EBB.002'))->toEqual($e3);
});

it('T4: semua sheet semua kelompok — total RO sama dengan total RO di master', function () {
    $master = masterRab();
    $diharapkan = [
        '7735.951' => totalRoRab($master, '7735.951', '7735.EBB.951'),
        '7733.001' => totalRoRab($master, '7733.001', '7733.EBB.001'),
        '7731.002' => totalRoRab($master, '7731.002', '7731.EBB.002'),
    ];
    $jumlahDetail = jumlahDetailRab($master, '7735.951');

    $s = rakitRab($master, ['7735.951' => null, '7733.001' => null, '7731.002' => null])['spreadsheet'];

    expect(totalRoRab($s, '7735.951', '7735.EBB.951'))->toEqual($diharapkan['7735.951'])
        ->and($diharapkan['7735.951'])->toEqual($jumlahDetail)
        ->and(totalRoRab($s, '7733.001', '7733.EBB.001'))->toEqual($diharapkan['7733.001'])
        ->and(totalRoRab($s, '7731.002', '7731.EBB.002'))->toEqual($diharapkan['7731.002']);
});

it('urutan keluaran mengikuti master walau kelompok dipilih terbalik', function () {
    $s = rakitRab(masterRab(), ['7735.951' => [28, 22]])['spreadsheet'];
    $ws = $s->getSheetByName('7735.951');

    expect(cariBarisRab($ws, 'A'))->toBeLessThan(cariBarisRab($ws, 'B'));
});

// --- T5: header/footer dilepas ---

it('T5: header dan footer dilepas — sheet hasil tanpa blok itu dan total tetap benar', function () {
    $master = masterRab();
    $diharapkan = jumlahDetailRab($master, '7735.951', [22]);

    $s = rakitRab($master, ['7735.951' => [22]], header: false, footer: false)['spreadsheet'];
    $ws = $s->getSheetByName('7735.951');

    expect($ws->getCell('A1')->getValue())->toBe('7735.EBB.951') // RO jadi baris pertama
        ->and(cariBarisRab($ws, 'Kementerian Fiktif'))->toBeNull()
        ->and(cariBarisRab($ws, 'Mengetahui', 'B'))->toBeNull()
        ->and(totalRoRab($s, '7735.951', '7735.EBB.951'))->toEqual($diharapkan);
});

// --- T7: kolom di luar area cetak ---

it('T7: kolom di luar area cetak tidak ikut', function () {
    $ws = rakitRab(masterRab(), ['7735.951' => null])['spreadsheet']->getSheetByName('7735.951');

    expect($ws->cellExists('Z19'))->toBeFalse()
        ->and($ws->cellExists('Z24'))->toBeFalse()
        ->and($ws->getHighestDataColumn())->toBe('X');
});

// --- T8: merges_skipped dan unmapped_formulas ---

it('T8: kasus normal menghasilkan merges_skipped 0, tanpa rumus tak terpetakan atau lintas-sheet', function () {
    $laporan = rakitRab(masterRab(), ['7735.951' => [22], '7733.001' => null])['laporan'];

    expect($laporan['merges_skipped'])->toBe(0)
        ->and($laporan['unmapped_formulas'])->toBe([])
        ->and($laporan['cross_sheet_formulas'])->toBe([]);
});

it('T8: merge yang melintasi potongan dihitung merges_skipped', function () {
    $master = masterRab();
    $master->getSheetByName('7735.951')->mergeCells('B21:B23'); // komponen + awal kelompok A = 2 potongan

    expect(rakitRab($master, ['7735.951' => [22]])['laporan']['merges_skipped'])->toBe(1);
});

it('T8: rumus yang merujuk baris yang tidak ikut dilaporkan, bukan diam-diam salah', function () {
    $master = masterRab();
    $master->getSheetByName('7735.951')->getCell('G10')->setValue('=X34'); // di header, merujuk sub 052 yang tidak dipilih

    $laporan = rakitRab($master, ['7735.951' => [22]])['laporan'];

    expect($laporan['unmapped_formulas'])->toBe(['7735.951!G10: X34']);
});

it('T8: rumus lintas-sheet dilaporkan', function () {
    $master = masterRab();
    $master->getSheetByName('7735.951')->getCell('G10')->setValue("='7733.001'!X19");

    expect(rakitRab($master, ['7735.951' => [22]])['laporan']['cross_sheet_formulas'])->toBe(['7735.951!G10']);
});

// --- T9 + fidelitas: buka ulang hasil dengan PhpSpreadsheet ---

it('T9: hasil disimpan lalu dibuka ulang — sheet, merge, tinggi baris, lebar/visibilitas kolom, gaya, area cetak terbawa', function () {
    $hasil = rakitRab(masterRab(), ['7735.951' => [22], '7733.001' => null]);
    $path = tempnam(sys_get_temp_dir(), 'rab-hasil-').'.xlsx';

    try {
        (new Xlsx($hasil['spreadsheet']))->save($path);
        $buka = IOFactory::createReader('Xlsx')->load($path);
        $ws = $buka->getSheetByName('7735.951');

        expect($buka->getSheetCount())->toBe(2)
            ->and(array_values($ws->getMergeCells()))->toBe(['A1:H1'])
            ->and($ws->getRowDimension(22)->getRowHeight())->toEqual(30)
            ->and($ws->getColumnDimension('B')->getWidth())->toEqual(40)
            ->and($ws->getColumnDimension('C')->getVisible())->toBeFalse()
            ->and($ws->getStyle('B19')->getFont()->getBold())->toBeTrue()
            ->and($ws->getStyle('B20')->getFont()->getBold())->toBeFalse()
            ->and($ws->getPageSetup()->getPrintArea())->toBe('A1:X33')
            ->and($ws->getPageSetup()->getRowsToRepeatAtTop())->toEqual([17, 17]); // dibaca ulang sebagai string
    } finally {
        @unlink($path);
    }
});

it('baris judul berulang dilepas bila barisnya tidak ikut (header dilepas)', function () {
    $ws = rakitRab(masterRab(), ['7735.951' => [22]], header: false)['spreadsheet']->getSheetByName('7735.951');

    expect($ws->getPageSetup()->isRowsToRepeatAtTopSet())->toBeFalse();
});

it('tidak mengubah sheet master sumber (tidak menambah dimensi baris)', function () {
    $master = masterRab();
    $sebelum = count($master->getSheetByName('7735.951')->getRowDimensions());

    $peta = (new RabMasterAnalyzer)->analyzeSpreadsheet($master);
    $sumber = $master->getSheetByName('7735.951');
    (new RabAssembler(new FormulaRemapper))->build($master, pilihanRab($peta, ['7735.951' => null]));

    expect(count($sumber->getRowDimensions()))->toBe($sebelum);
});

// --- Validasi masukan ---

it('menolak sheet terpilih tanpa kelompok', function () {
    $master = masterRab();
    $peta = (new RabMasterAnalyzer)->analyzeSpreadsheet($master);
    $pilihan = pilihanRab($peta, ['7735.951' => null]);
    $pilihan[0]['kelompok_baris'] = [];

    expect(fn () => (new RabAssembler(new FormulaRemapper))->build($master, $pilihan))
        ->toThrow(InvalidArgumentException::class);
});

it('menolak kelompok yang bukan milik sheet tersebut', function () {
    $master = masterRab();
    $peta = (new RabMasterAnalyzer)->analyzeSpreadsheet($master);
    $pilihan = pilihanRab($peta, ['7735.951' => [9999]]);

    expect(fn () => (new RabAssembler(new FormulaRemapper))->build($master, $pilihan))
        ->toThrow(InvalidArgumentException::class);
});

it('menolak pilihan kosong', function () {
    expect(fn () => (new RabAssembler(new FormulaRemapper))->build(masterRab(), []))
        ->toThrow(InvalidArgumentException::class);
});


// --- T6: hitung ulang di LibreOffice (rumus tanpa nilai tersimpan, jadi LibreOffice benar-benar menghitung) ---

it('T6: dihitung ulang LibreOffice — 0 error formula dan total sama dengan hitungan master', function () {
    $master = masterRab();
    $diharapkan = [
        '7735.951' => jumlahDetailRab($master, '7735.951', [34]), // memilih kelompok yang membuat baris bergeser
        '7733.001' => jumlahDetailRab($master, '7733.001'),
    ];

    $hasil = rakitRab($master, ['7735.951' => [34], '7733.001' => null]);
    $dir = sys_get_temp_dir().'/rab-lo-'.uniqid();
    mkdir($dir);

    try {
        $penulis = new Xlsx($hasil['spreadsheet']);
        $penulis->setPreCalculateFormulas(false); // tanpa nilai tersimpan => LibreOffice wajib menghitung
        $penulis->save("{$dir}/hasil.xlsx");

        mkdir("{$dir}/keluar");
        exec(sprintf(
            'cd %1$s && soffice -env:UserInstallation=file://%1$s/profil --headless --convert-to xlsx --outdir %1$s/keluar %1$s/hasil.xlsx 2>&1',
            escapeshellarg($dir)
        ), $keluaran, $kode);

        expect($kode)->toBe(0)->and(is_file("{$dir}/keluar/hasil.xlsx"))->toBeTrue();

        $dihitung = IOFactory::createReader('Xlsx')->load("{$dir}/keluar/hasil.xlsx");
        $error = [];
        foreach ($dihitung->getAllSheets() as $ws) {
            foreach ($ws->getCellCollection()->getCoordinates() as $koordinat) {
                $nilai = $ws->getCell($koordinat)->getOldCalculatedValue();
                if (is_string($nilai) && (str_starts_with($nilai, '#') || str_starts_with($nilai, 'Err:'))) {
                    $error[] = "{$ws->getTitle()}!{$koordinat}={$nilai}";
                }
            }
        }

        $ro1 = $dihitung->getSheetByName('7735.951');
        $ro2 = $dihitung->getSheetByName('7733.001');

        expect($error)->toBe([])
            ->and((float) $ro1->getCell('X'.cariBarisRab($ro1, '7735.EBB.951'))->getOldCalculatedValue())->toEqual($diharapkan['7735.951'])
            ->and((float) $ro1->getCell('F10')->getOldCalculatedValue())->toEqual($diharapkan['7735.951'])
            ->and((float) $ro2->getCell('X'.cariBarisRab($ro2, '7733.EBB.001'))->getOldCalculatedValue())->toEqual($diharapkan['7733.001']);
    } finally {
        exec('rm -rf '.escapeshellarg($dir));
    }
})->skip(fn () => trim((string) shell_exec('command -v soffice')) === '', 'Butuh LibreOffice (soffice) terpasang.');
