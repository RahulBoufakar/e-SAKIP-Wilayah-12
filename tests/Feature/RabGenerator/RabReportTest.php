<?php

use App\Models\FileExcel;
use App\Models\Kelompok;
use App\Models\Sheet;
use App\Services\RabMasterAnalyzer;
use App\Services\RabMasterStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
 * Master uji FIKTIF dibangun dari kode (06: jangan memasukkan master asli ke repositori).
 * Sheet "7735.951": header 1-18 | RO 19-20 | 051 (21) -> sub A 22-32 (=3000) | 052 (33) -> sub A 34-36 (=700)
 * | footer 37-40. Sheet "7733.001": RO + 051 -> sub A (tanpa rumus).
 */

function rabRepMasterXlsx(?Closure $ubah = null): string
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
    foreach ([19 => ['7733.EBB.001', 'RO Lain'], 21 => ['051', 'Komponen Lain'], 22 => ['A', 'Sub Lain'], 23 => ['521211', 'Akun Lain'], 24 => [null, '- Detail']] as $no => [$a, $b]) {
        $a !== null && $lain->getCell("A{$no}")->setValueExplicit($a, DataType::TYPE_STRING);
        $lain->getCell("B{$no}")->setValueExplicit($b, DataType::TYPE_STRING);
    }
    $lain->getPageSetup()->setPrintArea('A1:X26');

    if ($ubah) {
        $ubah($s);
    }

    $path = tempnam(sys_get_temp_dir(), 'rab-rep-').'.xlsx';
    (new Xlsx($s))->save($path);

    return $path;
}

/** Simpan master uji ke disk private, simpan hasil deteksinya ke database, lalu aktifkan. */
function rabRepFileAktif(string $nama = 'uji.xlsx', ?Closure $ubah = null, bool $aktifkan = true): FileExcel
{
    $sumber = rabRepMasterXlsx($ubah);
    $path = "rab-master/{$nama}";
    Storage::disk('private')->put($path, file_get_contents($sumber));
    unlink($sumber);

    $absolut = Storage::disk('private')->path($path);
    $file = FileExcel::create(['nama_file' => $nama, 'path' => $path, 'hash_sha256' => hash_file('sha256', $absolut)]);
    app(RabMasterStore::class)->simpan($file, app(RabMasterAnalyzer::class)->analyze($absolut));

    if ($aktifkan) {
        $file->aktifkan();
    }

    return $file;
}

function rabRepSheet(FileExcel $file, string $kode): Sheet
{
    return Sheet::where('file_excel_id', $file->id)->where('kode_sheet', $kode)->firstOrFail();
}

function rabRepKelompokId(Sheet $sheet, int $barisAwal): int
{
    return Kelompok::where('baris_awal', $barisAwal)
        ->whereHas('kategori', fn ($q) => $q->where('sheet_id', $sheet->id))
        ->firstOrFail()->id;
}

function rabRepPreview($test, FileExcel $file, array $sheets, $user = null)
{
    return $test->actingAs($user ?? $test->timKerja)
        ->postJson(route('tim-kerja.rab-generator.preview'), ['file_excel_id' => $file->id, 'sheets' => $sheets]);
}

beforeEach(function () {
    config(['rab_generator.enabled' => true]);
    Storage::fake('private');
    $this->timKerja = userWithRole('tim_kerja');
});

// --- Akses ---

it('mengembalikan 404 untuk tim kerja bila flag rab_generator.enabled nonaktif', function () {
    $file = rabRepFileAktif();
    config(['rab_generator.enabled' => false]);

    rabRepPreview($this, $file, [])->assertNotFound();
});

it('memblokir role selain tim_kerja (403) dan tamu (401)', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');
    $item = [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]];

    rabRepPreview($this, $file, $item, userWithRole('admin'))->assertForbidden();

    auth()->forgetGuards();
    $this->postJson(route('tim-kerja.rab-generator.preview'), [])->assertUnauthorized();
});

// --- Preview ---

it('preview merakit sheet terpilih, menyimpan berkas temp privat, dan mengisi cache terikat user', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');

    $response = rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]]);

    $response->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonStructure(['status', 'preview_token', 'previews' => [['judul', 'html']]]);

    $token = $response->json('preview_token');
    $html = $response->json('previews.0.html');

    expect($token)->toMatch('/^[0-9a-f-]{36}$/')
        ->and($response->json('previews.0.judul'))->toBe('7735.951')
        ->and($html)->toContain('Layanan Fiktif')->toContain('Komponen Dua')->not->toContain('Komponen Satu')
        ->and(Cache::get("rab_report:{$token}")['user_id'])->toBe($this->timKerja->id);
    Storage::disk('private')->assertExists("temp/{$token}.xlsx");
    Storage::disk('public')->assertMissing("temp/{$token}.xlsx");

    $hasil = IOFactory::createReader('Xlsx')->load(Storage::disk('private')->path("temp/{$token}.xlsx"));
    expect($hasil->getSheetCount())->toBe(1)
        ->and((float) $hasil->getSheet(0)->getCell('X19')->getCalculatedValue())->toEqual(700);
});

it('preview dua sheet menghasilkan dua tab pratinjau', function () {
    $file = rabRepFileAktif();
    $besar = rabRepSheet($file, '7735.951');
    $lain = rabRepSheet($file, '7733.001');

    $response = rabRepPreview($this, $file, [
        ['sheet_id' => $besar->id, 'kelompok_ids' => [rabRepKelompokId($besar, 22)]],
        ['sheet_id' => $lain->id, 'kelompok_ids' => [rabRepKelompokId($lain, 22)]],
    ]);

    $response->assertOk();
    expect(array_column($response->json('previews'), 'judul'))->toBe(['7735.951', '7733.001']);
});

it('header dan footer yang dilepas tidak muncul di pratinjau', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');

    $html = rabRepPreview($this, $file, [[
        'sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)], 'header' => false, 'footer' => false,
    ]])->assertOk()->json('previews.0.html');

    expect($html)->toContain('Layanan Fiktif')->not->toContain('Kementerian Fiktif')->not->toContain('Mengetahui');
});

// --- Validasi (S1, S2) ---

it('S2: sheet terpilih tanpa kelompok ditolak 422', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');

    rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => []]])
        ->assertStatus(422)->assertJsonValidationErrors('sheets.0.kelompok_ids');
});

it('S1: kelompok milik sheet lain ditolak 422', function () {
    $file = rabRepFileAktif();
    $besar = rabRepSheet($file, '7735.951');
    $lain = rabRepSheet($file, '7733.001');

    rabRepPreview($this, $file, [['sheet_id' => $besar->id, 'kelompok_ids' => [rabRepKelompokId($lain, 22)]]])
        ->assertStatus(422)->assertJsonValidationErrors('sheets');
});

it('S1: sheet milik file master yang tidak aktif ditolak 422', function () {
    $lama = rabRepFileAktif('lama.xlsx', null, aktifkan: false);
    $aktif = rabRepFileAktif('aktif.xlsx');
    $sheetLama = rabRepSheet($lama, '7735.951');

    rabRepPreview($this, $aktif, [['sheet_id' => $sheetLama->id, 'kelompok_ids' => [rabRepKelompokId($sheetLama, 22)]]])
        ->assertStatus(422)->assertJsonValidationErrors('sheets');
});

it('menolak pilihan yang mengacu ke file master yang sudah diganti', function () {
    $lama = rabRepFileAktif('lama.xlsx');
    $sheet = rabRepSheet($lama, '7735.951');
    $baru = rabRepFileAktif('baru.xlsx'); // diaktifkan admin setelah modal dibuka

    rabRepPreview($this, $lama, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 22)]]])
        ->assertStatus(422)->assertJsonValidationErrors('file_excel_id');

    expect($baru->fresh()->aktif)->toBeTrue();
});

it('menolak preview bila belum ada file master aktif', function () {
    $file = rabRepFileAktif('nonaktif.xlsx', null, aktifkan: false);
    $sheet = rabRepSheet($file, '7735.951');

    rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 22)]]])
        ->assertStatus(422)->assertJsonValidationErrors('file_excel_id');
});

it('menolak perakitan yang rumusnya merujuk bagian tak terpilih, tanpa meninggalkan berkas temp atau cache', function () {
    $file = rabRepFileAktif('rumus.xlsx', fn (Spreadsheet $s) => $s->getSheetByName('7735.951')->getCell('G10')->setValue('=X34'));
    $sheet = rabRepSheet($file, '7735.951');

    rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 22)]]]) // 052 tidak dipilih
        ->assertStatus(422)->assertJsonValidationErrors('pilihan');

    expect(Storage::disk('private')->allFiles())->toBe(['rab-master/rumus.xlsx']);
});

// --- Unduh (S3, S4, S5) ---

it('unduh mengirim berkas yang sama dengan hasil pratinjau sebagai attachment xlsx', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');
    $token = rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]])->json('preview_token');

    $response = $this->actingAs($this->timKerja)->get(route('tim-kerja.rab-generator.unduh', $token));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('attachment')->toContain('.xlsx')
        ->and($response->streamedContent())->toBe(Storage::disk('private')->get("temp/{$token}.xlsx"));
});

it('S3: token milik user lain ditolak 403', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');
    $token = rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]])->json('preview_token');

    $lain = userWithRole('tim_kerja', ['email' => 'lain-rab@test.local']);

    $this->actingAs($lain)->get(route('tim-kerja.rab-generator.unduh', $token))->assertForbidden();
});

it('S4: token kedaluwarsa (> 20 menit) ditolak 404 dengan pesan jelas', function () {
    $file = rabRepFileAktif();
    $sheet = rabRepSheet($file, '7735.951');
    $token = rabRepPreview($this, $file, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]])->json('preview_token');

    $this->travel(21)->minutes();

    $this->actingAs($this->timKerja)->get(route('tim-kerja.rab-generator.unduh', $token))
        ->assertNotFound()->assertSee('kedaluwarsa');
});

it('token yang bukan UUID ditolak 404 tanpa menyentuh cache atau disk', function () {
    $this->actingAs($this->timKerja)->get('/tim-kerja/rab-generator/unduh/../../etc/passwd')->assertNotFound();
    $this->actingAs($this->timKerja)->get(route('tim-kerja.rab-generator.unduh', 'bukan-uuid'))->assertNotFound();
});

it('S5: master diganti setelah pratinjau — unduh tetap memakai berkas temp lama', function () {
    $lama = rabRepFileAktif('lama.xlsx');
    $sheet = rabRepSheet($lama, '7735.951');
    $token = rabRepPreview($this, $lama, [['sheet_id' => $sheet->id, 'kelompok_ids' => [rabRepKelompokId($sheet, 34)]]])->json('preview_token');

    rabRepFileAktif('baru.xlsx'); // master baru aktif

    $this->actingAs($this->timKerja)->get(route('tim-kerja.rab-generator.unduh', $token))->assertOk();
});

// --- Throttle (S7) ---

it('S7: permintaan preview berlebih mendapat 429', function () {
    $file = rabRepFileAktif();

    for ($i = 0; $i < 10; $i++) {
        rabRepPreview($this, $file, [])->assertStatus(422); // ditolak validasi, tetap dihitung throttle
    }

    rabRepPreview($this, $file, [])->assertStatus(429);
});

// --- Pohon pilihan untuk modal (04 §B) ---

it('pohon mengembalikan struktur file master AKTIF untuk modal', function () {
    rabRepFileAktif('nonaktif.xlsx', null, aktifkan: false);
    $aktif = rabRepFileAktif('aktif.xlsx');

    $response = $this->actingAs($this->timKerja)->getJson(route('tim-kerja.rab-generator.pohon'));

    $response->assertOk()
        ->assertJsonPath('file_excel_id', $aktif->id)
        ->assertJsonCount(2, 'sheets')
        ->assertJsonPath('sheets.0.kode_sheet', '7735.951')
        ->assertJsonPath('sheets.0.nama_sheet', 'Layanan Fiktif')
        ->assertJsonPath('sheets.0.ada_header', true)
        ->assertJsonPath('sheets.0.ada_footer', true)
        ->assertJsonPath('sheets.0.kategori.0.kode', '051')
        ->assertJsonPath('sheets.0.kategori.0.nama', 'Komponen Satu')
        ->assertJsonPath('sheets.0.kategori.0.kelompok.0.kode', 'A')
        ->assertJsonPath('sheets.0.kategori.0.kelompok.0.label', 'Sub A satu');

    // ID dari pohon langsung bisa dipakai untuk preview (alur modal sebenarnya)
    $sheet = $response->json('sheets.0');
    rabRepPreview($this, $aktif, [['sheet_id' => $sheet['id'], 'kelompok_ids' => [$sheet['kategori'][1]['kelompok'][0]['id']]]])->assertOk();
});

it('pohon memakai label cadangan "Sub {kode}" untuk kelompok tanpa nama', function () {
    $file = rabRepFileAktif();
    Kelompok::whereHas('kategori', fn ($q) => $q->where('sheet_id', rabRepSheet($file, '7735.951')->id))
        ->where('baris_awal', 22)->update(['nama_kelompok' => null]);

    $this->actingAs($this->timKerja)->getJson(route('tim-kerja.rab-generator.pohon'))
        ->assertJsonPath('sheets.0.kategori.0.kelompok.0.label', 'Sub A');
});

it('pohon: 404 dengan pesan jelas bila belum ada master aktif, 403 untuk non-tim_kerja, 404 bila flag nonaktif', function () {
    $this->actingAs($this->timKerja)->getJson(route('tim-kerja.rab-generator.pohon'))
        ->assertNotFound()->assertJsonPath('message', 'Belum ada file master RAB yang aktif. Hubungi Administrator.');

    $this->actingAs(userWithRole('admin'))->getJson(route('tim-kerja.rab-generator.pohon'))->assertForbidden();

    rabRepFileAktif();
    config(['rab_generator.enabled' => false]);
    $this->actingAs($this->timKerja)->getJson(route('tim-kerja.rab-generator.pohon'))->assertNotFound();
});

// --- Komponen modal ---

it('komponen modal merender pemicu berupa tautan, bukan tombol (aman di dalam fieldset yang dinonaktifkan)', function () {
    $this->blade('<x-rab-generator.modal />')
        ->assertSee('Unduh Template Excel')
        ->assertSee('<a href="#" role="button"', false)
        ->assertDontSee('<button', false);
});
