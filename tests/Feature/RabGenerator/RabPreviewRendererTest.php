<?php

use App\Services\RabPreviewRenderer;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/*
 * Langkah 10 (02-arsitektur.md): perbaikan tampilan pratinjau. Workbook uji FIKTIF dan kecil;
 * yang diuji hanya perilaku renderer, bukan perakitan.
 */

function previewHtml(?Closure $ubah = null): string
{
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('uji');
    $ws->getCell('A1')->setValueExplicit('Teks biasa', DataType::TYPE_STRING);

    if ($ubah) {
        $ubah($s, $ws);
    }

    return (new RabPreviewRenderer)->render($s)[0]['html'];
}

it('sel string berformat jam tidak tampil 0:00, nilai teksnya tetap', function () {
    $html = previewHtml(function ($s, $ws) {
        $ws->getCell('A2')->setValueExplicit('1.', DataType::TYPE_STRING);
        $ws->getStyle('A2')->getNumberFormat()->setFormatCode('h:mm');
    });

    expect($html)->toContain('1.')->not->toContain('0:00');
});

it('sel angka berformat jam sungguhan tetap diformat sebagai jam', function () {
    $html = previewHtml(function ($s, $ws) {
        $ws->getCell('A2')->setValue(0.5); // 12:00
        $ws->getStyle('A2')->getNumberFormat()->setFormatCode('h:mm');
    });

    expect($html)->toContain('12:00');
});

it('garis kisi dimatikan pada tabel pratinjau', function () {
    $html = previewHtml(fn ($s, $ws) => $ws->setShowGridlines(true));

    expect($html)->not->toMatch("/<table[^>]*class=['\"][^'\"]*gridlines/");
});

it('semua sel nowrap, kecuali gaya yang memang wrap di Excel', function () {
    $html = previewHtml(function ($s, $ws) {
        $ws->getCell('A2')->setValueExplicit('Teks panjang yang wrap', DataType::TYPE_STRING);
        $ws->getStyle('A2')->getAlignment()->setWrapText(true);
    });

    expect($html)->toContain('table td, table th { white-space: nowrap; }');

    // Kelas gaya sel A2 (wrap) dikecualikan; kelas gaya sel A1 (tidak wrap) tidak.
    preg_match_all('/td\.style(\d+), th\.style\d+ \{ white-space: normal; \}/', $html, $cocok);
    expect($cocok[1])->toHaveCount(1);

    $kelasWrap = $cocok[1][0];
    expect($html)->toMatch("/<td[^>]*class=['\"][^'\"]*\\bstyle{$kelasWrap}\\b[^>]*>Teks panjang yang wrap/")
        ->and($html)->not->toMatch("/<td[^>]*class=['\"][^'\"]*\\bstyle{$kelasWrap}\\b[^>]*>Teks biasa/");
});

it('aturan nowrap berada di dalam blok style milik dokumen pratinjau', function () {
    $html = previewHtml();

    $posisiNowrap = strpos($html, 'white-space: nowrap');

    expect($posisiNowrap)->toBeInt()
        ->and($posisiNowrap)->toBeLessThan(strpos($html, '</style>'));
});
