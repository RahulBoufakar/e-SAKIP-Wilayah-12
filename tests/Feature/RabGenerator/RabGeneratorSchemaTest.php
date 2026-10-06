<?php

use App\Models\FileExcel;
use App\Models\Footer;
use App\Models\Header;
use App\Models\Kategori;
use App\Models\Kelompok;
use App\Models\Sheet;
use Illuminate\Database\QueryException;

function rabFile(array $attrs = []): FileExcel
{
    return FileExcel::create($attrs + [
        'nama_file' => 'master-uji.xlsx',
        'path' => 'rab-master/master-uji.xlsx',
        'hash_sha256' => str_repeat('a', 64),
    ]);
}

function rabSheet(FileExcel $file, string $kode = '7735.951', array $attrs = []): Sheet
{
    return Sheet::create($attrs + [
        'file_excel_id' => $file->id,
        'kode_sheet' => $kode,
        'nama_sheet' => "RO {$kode}",
        'urutan' => 1,
        'ro_baris_awal' => 19,
        'ro_baris_akhir' => 20,
        'kolom_akhir' => 'X',
    ]);
}

function rabKategori(Sheet $sheet, string $kode = '051'): Kategori
{
    return Kategori::create([
        'sheet_id' => $sheet->id,
        'kode_kategori' => $kode,
        'nama_kategori' => "Komponen {$kode}",
        'baris_awal' => 21,
        'baris_akhir' => 60,
        'urutan' => 1,
    ]);
}

function rabKelompok(Kategori $kategori, string $kode = 'A', ?string $nama = null): Kelompok
{
    return Kelompok::create([
        'kategori_id' => $kategori->id,
        'kode_kelompok' => $kode,
        'nama_kelompok' => $nama,
        'baris_awal' => 22,
        'baris_akhir' => 40,
        'urutan' => 1,
    ]);
}

it('flag rab_generator.enabled default nonaktif supaya alur template lama tetap jalan', function () {
    expect(config('rab_generator.enabled'))->toBeFalse();
});

it('relasi file_excel -> sheet -> header/footer/kategori -> kelompok resolve dengan benar', function () {
    $file = rabFile();
    $sheet = rabSheet($file);
    $header = Header::create(['sheet_id' => $sheet->id, 'baris_awal' => 1, 'baris_akhir' => 18]);
    $footer = Footer::create(['sheet_id' => $sheet->id, 'baris_awal' => 61, 'baris_akhir' => 66]);
    $kategori = rabKategori($sheet);
    $kelompok = rabKelompok($kategori);

    expect($file->sheets->first()->is($sheet))->toBeTrue()
        ->and($sheet->fileExcel->is($file))->toBeTrue()
        ->and($sheet->header->is($header))->toBeTrue()
        ->and($sheet->footer->is($footer))->toBeTrue()
        ->and($sheet->kategori->first()->is($kategori))->toBeTrue()
        ->and($kategori->kelompok->first()->is($kelompok))->toBeTrue()
        ->and($kelompok->kategori->is($kategori))->toBeTrue()
        ->and($header->sheet->is($sheet))->toBeTrue();
});

it('header dan footer default otomatis = true', function () {
    $sheet = rabSheet(rabFile());
    $header = Header::create(['sheet_id' => $sheet->id, 'baris_awal' => 1, 'baris_akhir' => 18]);
    $footer = Footer::create(['sheet_id' => $sheet->id, 'baris_awal' => 61, 'baris_akhir' => 66]);

    expect($header->fresh()->otomatis)->toBeTrue()
        ->and($footer->fresh()->otomatis)->toBeTrue();
});

// --- Batas unik (03: kode bukan kunci global) ---

it('menolak kode_sheet ganda dalam satu file, tapi mengizinkan di file lain', function () {
    $fileA = rabFile();
    $fileB = rabFile(['hash_sha256' => str_repeat('b', 64)]);

    rabSheet($fileA, '7735.951');
    rabSheet($fileB, '7735.951'); // file berbeda: boleh

    expect(Sheet::where('kode_sheet', '7735.951')->count())->toBe(2);

    rabSheet($fileA, '7735.951'); // file sama: ditolak
})->throws(QueryException::class);

it('menolak kode_kategori ganda dalam satu sheet, tapi mengizinkan di sheet lain', function () {
    $file = rabFile();
    $sheetA = rabSheet($file, '7733.001');
    $sheetB = rabSheet($file, '7735.951');

    rabKategori($sheetA, '051');
    rabKategori($sheetB, '051'); // sheet berbeda: boleh

    expect(Kategori::where('kode_kategori', '051')->count())->toBe(2);

    rabKategori($sheetA, '051'); // sheet sama: ditolak
})->throws(QueryException::class);

it('kode_kelompok TIDAK unik: dua sub berlabel sama dalam satu kategori diizinkan', function () {
    $kategori = rabKategori(rabSheet(rabFile()));

    rabKelompok($kategori, 'D');
    rabKelompok($kategori, 'D');

    expect($kategori->kelompok()->where('kode_kelompok', 'D')->count())->toBe(2);
});

it('menolak lebih dari satu header atau footer per sheet (1:1)', function () {
    $sheet = rabSheet(rabFile());
    Header::create(['sheet_id' => $sheet->id, 'baris_awal' => 1, 'baris_akhir' => 18]);

    Header::create(['sheet_id' => $sheet->id, 'baris_awal' => 1, 'baris_akhir' => 10]);
})->throws(QueryException::class);

it('menolak footer kedua pada sheet yang sama (1:1)', function () {
    $sheet = rabSheet(rabFile());
    Footer::create(['sheet_id' => $sheet->id, 'baris_awal' => 61, 'baris_akhir' => 66]);

    Footer::create(['sheet_id' => $sheet->id, 'baris_awal' => 61, 'baris_akhir' => 70]);
})->throws(QueryException::class);

// --- Label kelompok ---

it('label kelompok memakai nama bila ada, cadangan "Sub {kode}" bila kosong', function () {
    $kategori = rabKategori(rabSheet(rabFile()));

    expect(rabKelompok($kategori, 'A', 'Belanja Barang')->label)->toBe('Belanja Barang')
        ->and(rabKelompok($kategori, 'B', null)->label)->toBe('Sub B')
        ->and(rabKelompok($kategori, 'C', '')->label)->toBe('Sub C');
});

// --- Siklus file master ---

it('aktifkan() menjadikan hanya satu file master aktif', function () {
    $lama = rabFile(['aktif' => true]);
    $baru = rabFile(['hash_sha256' => str_repeat('b', 64)]);

    $baru->aktifkan();

    expect($lama->fresh()->aktif)->toBeFalse()
        ->and($baru->fresh()->aktif)->toBeTrue()
        ->and(FileExcel::aktif()->count())->toBe(1);
});

it('file_excel mencatat pengunggah, dan diunggah_oleh jadi null saat user dihapus', function () {
    $admin = userWithRole('admin');
    $file = rabFile(['diunggah_oleh' => $admin->id]);

    expect($file->pengunggah->is($admin))->toBeTrue()
        ->and($file->fresh()->tanggal_upload)->not->toBeNull();

    $admin->delete();

    expect($file->fresh()->diunggah_oleh)->toBeNull();
});

it('menghapus file_excel ikut menghapus seluruh struktur hasil deteksinya', function () {
    $file = rabFile();
    $sheet = rabSheet($file);
    Header::create(['sheet_id' => $sheet->id, 'baris_awal' => 1, 'baris_akhir' => 18]);
    Footer::create(['sheet_id' => $sheet->id, 'baris_awal' => 61, 'baris_akhir' => 66]);
    rabKelompok(rabKategori($sheet));

    $file->delete();

    expect(Sheet::count())->toBe(0)
        ->and(Header::count())->toBe(0)
        ->and(Footer::count())->toBe(0)
        ->and(Kategori::count())->toBe(0)
        ->and(Kelompok::count())->toBe(0);
});
