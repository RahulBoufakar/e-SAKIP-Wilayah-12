<?php

use App\Services\RabMasterAnalyzer;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/*
 * Master uji FIKTIF dibangun dari kode (06: jangan memasukkan master asli ke repositori).
 * Struktur mengikuti 05: header 1-18, RO di baris 19, lalu komponen/sub/akun/detail.
 */

/** @param array<int, array{0: string|int|null, 1: ?string}> $baris */
function masterUjiIsi(Worksheet $ws, array $baris): void
{
    foreach ($baris as $no => [$a, $b]) {
        if ($a !== null && $a !== '') {
            is_int($a)
                ? $ws->getCell([1, $no])->setValue($a) // akun tersimpan sebagai angka
                : $ws->getCell([1, $no])->setValueExplicit($a, DataType::TYPE_STRING);
        }
        if ($b !== null && $b !== '') {
            $ws->getCell([2, $no])->setValueExplicit($b, DataType::TYPE_STRING);
        }
    }
}

function masterUjiHeader(Worksheet $ws): void
{
    masterUjiIsi($ws, [1 => ['KOP', 'Kementerian Fiktif'], 17 => ['KODE', 'URAIAN']]);
}

function masterUjiSheetLengkap(Worksheet $ws): void
{
    $ws->setTitle('7735.951');
    masterUjiHeader($ws);
    masterUjiIsi($ws, [
        19 => ['7735.EBB.951', 'Layanan Fiktif'],
        21 => ['051', 'Komponen Satu'],
        22 => ['A', 'Sub A satu'],
        23 => ['521211', 'Belanja Honor'],
        24 => [null, '- Honor narasumber'],
        25 => [null, '- Honor moderator'],
        26 => ['521213', 'Belanja Perjalanan'],
        27 => [null, '- Tiket'],
        28 => ['B', null], // sub tanpa nama
        29 => ['524111', 'Belanja Perjalanan Dinas'],
        30 => [null, '- Transport'],
        32 => ['052', 'Komponen Dua'],
        33 => ['A', 'Sub A dua'],
        34 => ['521211', 'Belanja Honor'],
        35 => [null, '- Honor'],
        38 => [null, 'Mengetahui'],
        40 => [null, 'Pejabat Fiktif'],
    ]);
    $ws->getPageSetup()->setPrintArea('A1:X40');
}

function masterUjiSheetTanpaAreaCetak(Spreadsheet $s): void
{
    $ws = $s->createSheet();
    $ws->setTitle('7733.001');
    masterUjiHeader($ws);
    masterUjiIsi($ws, [
        19 => ['7733.EBB.001', 'RO Kedua'],
        20 => ['051', 'Komponen Satu'],
        21 => ['A', 'Sub A'],
        22 => [521211, 'Akun angka'], // int, bukan string
        23 => [null, '- detail'],
    ]);
    $ws->getCell('F23')->setValue('catatan'); // kolom data terjauh = F
}

function masterUji(): Spreadsheet
{
    $s = new Spreadsheet();
    masterUjiSheetLengkap($s->getActiveSheet());
    masterUjiSheetTanpaAreaCetak($s);

    $catatan = $s->createSheet();
    $catatan->setTitle('Catatan');
    masterUjiIsi($catatan, [1 => ['Hanya teks', 'tanpa kode RO']]);

    return $s;
}

function ringkasKategori(array $sheet): array
{
    return array_map(fn ($k) => [$k['kode_kategori'], $k['nama_kategori'], $k['baris_awal'], $k['baris_akhir']], $sheet['kategori']);
}

function ringkasKelompok(array $kategori): array
{
    return array_map(fn ($g) => [$g['kode_kelompok'], $g['nama_kelompok'], $g['baris_awal'], $g['baris_akhir'], $g['urutan']], $kategori['kelompok']);
}

// --- T10: deteksi pada master uji ---

it('mendeteksi sheet RO, header, footer, dan kolom akhir dari area cetak', function () {
    $hasil = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji());
    $sheet = $hasil['sheets'][0];

    expect($sheet['kode_sheet'])->toBe('7735.951')
        ->and($sheet['nama_sheet'])->toBe('Layanan Fiktif')
        ->and($sheet['urutan'])->toBe(1)
        ->and($sheet['ro_baris_awal'])->toBe(19)
        ->and($sheet['ro_baris_akhir'])->toBe(20)
        ->and($sheet['header'])->toBe(['baris_awal' => 1, 'baris_akhir' => 18])
        ->and($sheet['footer'])->toBe(['baris_awal' => 36, 'baris_akhir' => 40])
        ->and($sheet['kolom_akhir'])->toBe('X');
});

it('mendeteksi kategori (komponen) sebagai segmen judulnya saja, dengan urutan', function () {
    $sheet = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji())['sheets'][0];

    expect(ringkasKategori($sheet))->toBe([
        ['051', 'Komponen Satu', 21, 21],
        ['052', 'Komponen Dua', 32, 32],
    ])
        ->and($sheet['kategori'][0]['urutan'])->toBe(1)
        ->and($sheet['kategori'][1]['urutan'])->toBe(2);
});

it('mendeteksi kelompok (sub) dari baris sub sampai akhir akun terakhirnya', function () {
    $sheet = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji())['sheets'][0];

    expect(ringkasKelompok($sheet['kategori'][0]))->toBe([
        ['A', 'Sub A satu', 22, 27, 1],
        ['B', null, 28, 31, 2], // baris 31 = spasi sebelum komponen berikutnya
    ])
        // segmen terakhir berakhir di detail terakhir (35), bukan di footer
        ->and(ringkasKelompok($sheet['kategori'][1]))->toBe([
            ['A', 'Sub A dua', 33, 35, 1],
        ]);
});

it('kode kelompok dan komponen yang sama di sheet/komponen berbeda bukan peringatan', function () {
    $hasil = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji());

    // sub "A" ada di komponen 051 dan 052; komponen "051" ada di kedua sheet.
    expect(implode("\n", $hasil['sheets'][0]['peringatan']))->not->toContain('ganda')
        ->and($hasil['sheets'][1]['peringatan'])->toBe([]);
});

it('membaca kode akun yang tersimpan sebagai angka dan memakai kolom data terjauh bila tanpa area cetak', function () {
    $sheet = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji())['sheets'][1];

    expect($sheet['kode_sheet'])->toBe('7733.001')
        ->and($sheet['urutan'])->toBe(2)
        ->and($sheet['kolom_akhir'])->toBe('F')
        ->and(ringkasKelompok($sheet['kategori'][0]))->toBe([['A', 'Sub A', 21, 23, 1]])
        // badan berakhir di 23 = baris data terakhir => tidak ada footer
        ->and($sheet['footer'])->toBeNull();
});

it('melewati sheet tanpa kode RO dengan peringatan tingkat file', function () {
    $hasil = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji());

    expect($hasil['sheets'])->toHaveCount(2)
        ->and($hasil['peringatan'])->toHaveCount(1)
        ->and($hasil['peringatan'][0])->toContain('Catatan')->toContain('kode RO tidak ditemukan');
});

// --- Peringatan struktur (03, aturan validasi no. 6) ---

it('memberi peringatan untuk nama kelompok kosong', function () {
    $sheet = (new RabMasterAnalyzer)->analyzeSpreadsheet(masterUji())['sheets'][0];

    expect($sheet['peringatan'])->toHaveCount(1)
        ->and($sheet['peringatan'][0])->toContain('Kelompok B')->toContain('tidak punya nama');
});

it('memberi peringatan untuk kode ganda dan baris yatim', function () {
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7731.001');
    masterUjiHeader($ws);
    masterUjiIsi($ws, [
        19 => ['7731.EBB.001', 'RO Uji Peringatan'],
        20 => ['051', 'K1'],
        21 => ['D', 'Sub D pertama'],
        22 => ['D', 'Sub D kedua'],
        23 => ['E', null],
        24 => ['051', 'K1 lagi'],
        25 => ['521211', 'Akun yatim'],
    ]);

    $peringatan = implode("\n", (new RabMasterAnalyzer)->analyzeSpreadsheet($s)['sheets'][0]['peringatan']);

    expect($peringatan)->toContain('Kode kelompok ganda: D pada komponen 051')
        ->toContain('Kelompok E pada komponen 051 (baris 23) tidak punya nama')
        ->toContain('Kode komponen ganda: 051 (baris 20, 24)')
        ->toContain('Akun 521211 (baris 25) tidak berada di dalam kelompok');
});

it('hanya memakai RO pertama bila ada lebih dari satu kode RO dalam satu sheet', function () {
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7730.001');
    masterUjiHeader($ws);
    masterUjiIsi($ws, [
        19 => ['7730.EBB.001', 'RO Pertama'],
        20 => ['7730.EBB.002', 'RO Kedua'],
    ]);

    $sheet = (new RabMasterAnalyzer)->analyzeSpreadsheet($s)['sheets'][0];

    expect($sheet['nama_sheet'])->toBe('RO Pertama')
        ->and($sheet['peringatan'][0])->toContain('lebih dari satu kode RO');
});

it('header null bila RO ada di baris pertama', function () {
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7729.001');
    masterUjiIsi($ws, [1 => ['7729.EBB.001', 'RO Tanpa Header']]);

    expect((new RabMasterAnalyzer)->analyzeSpreadsheet($s)['sheets'][0]['header'])->toBeNull();
});

// --- Jalur file (analyze) ---

it('analyze() dari file xlsx menghasilkan peta yang sama dengan analisis workbook di memori', function () {
    $spreadsheet = masterUji();
    $path = tempnam(sys_get_temp_dir(), 'rab-uji-').'.xlsx';

    try {
        (new Xlsx($spreadsheet))->save($path);

        $dariFile = (new RabMasterAnalyzer)->analyze($path);
        $dariMemori = (new RabMasterAnalyzer)->analyzeSpreadsheet($spreadsheet);

        expect($dariFile)->toBe($dariMemori)
            ->and($dariFile['sheets'][0]['kolom_akhir'])->toBe('X'); // area cetak ikut tersimpan
    } finally {
        @unlink($path);
    }
});
