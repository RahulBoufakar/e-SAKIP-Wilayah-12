<?php

use App\Events\ActivityOccurred;
use App\Models\FileExcel;
use App\Models\Footer;
use App\Models\Header;
use App\Models\Kategori;
use App\Models\Kelompok;
use App\Models\Sheet;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/*
 * Master uji FIKTIF dibangun dari kode (06: jangan memasukkan master asli ke repositori).
 * Hasil deteksi yang diharapkan untuk sheet "7735.951":
 *   header 1-18 | RO 19-20 | komponen 051 (21) -> sub A 22-27, sub B 28-31 (tanpa nama)
 *   komponen 052 (32) -> sub A 33-35 | isi RAB berakhir di baris 35 | footer 36-40 (area cetak A1:X40)
 * Sheet "Catatan" tidak punya kode RO -> dilewati dengan peringatan tingkat file.
 */

function rabAdminMaster(): Spreadsheet
{
    $s = new Spreadsheet;
    $ws = $s->getActiveSheet();
    $ws->setTitle('7735.951');

    $isi = [
        1 => ['KOP', 'Kementerian Fiktif'],
        17 => ['KODE', 'URAIAN'],
        19 => ['7735.EBB.951', 'Layanan Fiktif'],
        21 => ['051', 'Komponen Satu'],
        22 => ['A', 'Sub A satu'],
        23 => ['521211', 'Belanja Honor'],
        24 => [null, '- Honor'],
        28 => ['B', null], // sub tanpa nama
        29 => ['524111', 'Belanja Dinas'],
        30 => [null, '- Transport'],
        32 => ['052', 'Komponen Dua'],
        33 => ['A', 'Sub A dua'],
        34 => ['521211', 'Belanja Honor'],
        35 => [null, '- Honor'],
        38 => [null, 'Mengetahui'],
        40 => [null, 'Pejabat Fiktif'],
    ];
    foreach ($isi as $no => [$a, $b]) {
        if ($a !== null) {
            $ws->getCell("A{$no}")->setValueExplicit($a, DataType::TYPE_STRING);
        }
        if ($b !== null) {
            $ws->getCell("B{$no}")->setValueExplicit($b, DataType::TYPE_STRING);
        }
    }
    $ws->mergeCells('A17:A18'); // di dalam header; memotongnya bila header diubah jadi 1-17
    $ws->getPageSetup()->setPrintArea('A1:X40');

    $catatan = $s->createSheet();
    $catatan->setTitle('Catatan');
    $catatan->getCell('A1')->setValueExplicit('Hanya teks', DataType::TYPE_STRING);

    return $s;
}

/** Unggah master uji lewat endpoint. $ubah memodifikasi workbook sebelum disimpan. */
function unggahMasterRab($test, ?Closure $ubah = null)
{
    $s = rabAdminMaster();
    if ($ubah) {
        $ubah($s);
    }

    $path = tempnam(sys_get_temp_dir(), 'rab-up-').'.xlsx';
    (new Xlsx($s))->save($path);

    $file = new UploadedFile($path, 'master-uji.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    return $test->actingAs($test->admin)->post(route('admin.rab-generator.store'), ['file' => $file]);
}

/** Kirim koreksi untuk satu sheet; nilai header/footer default = nilai tersimpan. */
function koreksiRab($test, Sheet $sheet, array $ubah = [])
{
    $sheet->load(['header', 'footer']);

    return $test->actingAs($test->admin)->put(route('admin.rab-generator.sheet.update', $sheet), $ubah + [
        'header_awal' => $sheet->header->baris_awal,
        'header_akhir' => $sheet->header->baris_akhir,
        'footer_awal' => $sheet->footer->baris_awal,
        'footer_akhir' => $sheet->footer->baris_akhir,
    ]);
}

beforeEach(function () {
    config(['rab_generator.enabled' => true]);
    Storage::fake('private');
    Event::fake([ActivityOccurred::class]);
    $this->admin = userWithRole('admin');
});

// --- Akses ---

it('mengembalikan 404 untuk admin bila flag rab_generator.enabled nonaktif', function () {
    config(['rab_generator.enabled' => false]);

    $this->actingAs($this->admin)->get(route('admin.rab-generator.index'))->assertNotFound();
});

it('memblokir non-admin dengan 403', function () {
    $timKerja = userWithRole('tim_kerja');

    $this->actingAs($timKerja)->get(route('admin.rab-generator.index'))->assertForbidden();
    $this->actingAs($timKerja)->post(route('admin.rab-generator.store'))->assertForbidden();
});

it('mengarahkan tamu (belum login) ke halaman login', function () {
    $this->get(route('admin.rab-generator.index'))->assertRedirect(route('login'));
});

// --- Unggah ---

it('unggah master menyimpan file di disk private dan seluruh hasil deteksi, lalu mengarahkan ke halaman tinjau', function () {
    $response = unggahMasterRab($this);

    $file = FileExcel::first();
    $response->assertRedirect(route('admin.rab-generator.show', $file));

    expect($file->nama_file)->toBe('master-uji.xlsx')
        ->and($file->aktif)->toBeFalse()
        ->and($file->hash_sha256)->toHaveLength(64)
        ->and($file->diunggah_oleh)->toBe($this->admin->id);
    Storage::disk('private')->assertExists($file->path);

    $sheet = Sheet::with(['header', 'footer', 'kategori.kelompok'])->firstOrFail();

    expect(Sheet::count())->toBe(1) // sheet "Catatan" dilewati
        ->and($sheet->kode_sheet)->toBe('7735.951')
        ->and($sheet->nama_sheet)->toBe('Layanan Fiktif')
        ->and([$sheet->ro_baris_awal, $sheet->ro_baris_akhir, $sheet->kolom_akhir])->toBe([19, 20, 'X'])
        ->and([$sheet->header->baris_awal, $sheet->header->baris_akhir, $sheet->header->otomatis])->toBe([1, 18, true])
        ->and([$sheet->footer->baris_awal, $sheet->footer->baris_akhir])->toBe([36, 40])
        ->and($sheet->kategori->pluck('kode_kategori')->all())->toBe(['051', '052'])
        ->and($sheet->kategori[0]->kelompok->pluck('kode_kelompok')->all())->toBe(['A', 'B'])
        ->and($sheet->kategori[0]->kelompok[1]->nama_kelompok)->toBeNull();

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'mengunggah file master RAB'));
});

it('peringatan yang tidak bisa dihitung ulang disimpan; peringatan struktur tidak (dihitung langsung)', function () {
    unggahMasterRab($this);
    $file = FileExcel::first();

    expect($file->peringatan['file'])->toHaveCount(1)
        ->and($file->peringatan['file'][0])->toContain('Catatan')
        ->and($file->peringatan['sheet']['7735.951'])->toBe([]) // "sub B tanpa nama" tidak disimpan
        ->and($file->jumlah_peringatan)->toBe(1);
});

it('file yang bukan xlsx valid ditolak dan tidak meninggalkan baris atau berkas', function () {
    $palsu = UploadedFile::fake()->create('rusak.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($this->admin)->post(route('admin.rab-generator.store'), ['file' => $palsu])->assertRedirect();

    expect(FileExcel::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('master tanpa satu pun sheet berkode RO ditolak dengan pesan jelas', function () {
    unggahMasterRab($this, function (Spreadsheet $s) {
        $s->removeSheetByIndex(0); // hanya sisa sheet "Catatan"
    })->assertSessionHas('feedback.type', 'error');

    expect(FileExcel::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

it('master dengan kode komponen ganda dalam satu sheet ditolak tanpa menulis apa pun', function () {
    unggahMasterRab($this, function (Spreadsheet $s) {
        $s->getSheetByName('7735.951')->getCell('A36')->setValueExplicit('051', DataType::TYPE_STRING);
    })->assertSessionHas('feedback.type', 'error');

    expect(FileExcel::count())->toBe(0)
        ->and(Sheet::count())->toBe(0)
        ->and(Storage::disk('private')->allFiles())->toBe([]);
});

// --- Tinjau ---

it('halaman daftar dan tinjau menampilkan struktur, nama, dan peringatan', function () {
    unggahMasterRab($this);
    $file = FileExcel::first();

    $this->actingAs($this->admin)->get(route('admin.rab-generator.index'))
        ->assertOk()->assertSee('master-uji.xlsx')->assertSee('Tidak aktif');

    $this->actingAs($this->admin)->get(route('admin.rab-generator.show', $file))
        ->assertOk()
        ->assertSee('7735.951')
        ->assertSee('Layanan Fiktif')
        ->assertSee('Komponen Satu')
        ->assertSee('Sub A satu')
        ->assertSee('Kelompok B pada komponen 051')  // peringatan struktur (dari database)
        ->assertSee('kode RO tidak ditemukan');      // peringatan tersimpan (tingkat file)
});

it('peringatan nama kosong hilang setelah admin mengisi nama kelompok', function () {
    unggahMasterRab($this);
    $sheet = Sheet::first();
    $kelompokB = Kelompok::where('kode_kelompok', 'B')->firstOrFail();

    koreksiRab($this, $sheet, ['nama' => [$kelompokB->id => '  Sub B diisi  ']])
        ->assertSessionHas('feedback.type', 'success');

    expect($kelompokB->fresh()->nama_kelompok)->toBe('Sub B diisi'); // di-trim

    $this->actingAs($this->admin)->get(route('admin.rab-generator.show', $sheet->fileExcel))
        ->assertOk()->assertSee('Sub B diisi')->assertDontSee('tidak punya nama');
});

// --- Koreksi ---

it('nama kelompok yang dikosongkan disimpan sebagai null', function () {
    unggahMasterRab($this);
    $sheet = Sheet::first();
    $kelompokA = Kelompok::where('nama_kelompok', 'Sub A satu')->firstOrFail();

    koreksiRab($this, $sheet, ['nama' => [$kelompokA->id => '   ']]);

    expect($kelompokA->fresh()->nama_kelompok)->toBeNull();
});

it('mengubah footer yang valid menyimpannya dan menandai otomatis = false (header tetap otomatis)', function () {
    unggahMasterRab($this);
    $sheet = Sheet::first();

    koreksiRab($this, $sheet, ['footer_awal' => 37, 'footer_akhir' => 40])
        ->assertSessionHas('feedback.type', 'success');

    $footer = Footer::firstOrFail();
    expect([$footer->baris_awal, $footer->baris_akhir, $footer->otomatis])->toBe([37, 40, false])
        ->and(Header::firstOrFail()->otomatis)->toBeTrue();

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'mengoreksi struktur master RAB') && str_contains($e->description, 'footer'));
});

it('submit tanpa perubahan tidak menulis apa pun dan tidak membuat audit log', function () {
    unggahMasterRab($this);
    Event::fake([ActivityOccurred::class]); // reset hitungan setelah unggah

    koreksiRab($this, Sheet::first())->assertSessionHas('feedback.message', 'Tidak ada perubahan.');

    Event::assertNotDispatched(ActivityOccurred::class);
});

it('menolak rentang header/footer yang melanggar aturan validasi dan tidak mengubah data', function (array $ubah, string $kolom) {
    unggahMasterRab($this);
    $sheet = Sheet::first();

    koreksiRab($this, $sheet, $ubah)->assertSessionHasErrorsIn("sheet{$sheet->id}", $kolom);

    expect(Header::firstOrFail()->baris_akhir)->toBe(18)
        ->and(Footer::firstOrFail()->baris_awal)->toBe(36)
        ->and(Header::firstOrFail()->otomatis)->toBeTrue();
})->with([
    'header menabrak baris RO (19)' => [['header_akhir' => 19], 'header_akhir'],
    'header memotong merge A17:A18' => [['header_akhir' => 17], 'header_akhir'],
    'footer menabrak isi RAB (35)' => [['footer_awal' => 35], 'footer_awal'],
    'footer melebihi jumlah baris sheet' => [['footer_akhir' => 999], 'footer_akhir'],
    'akhir lebih kecil dari awal' => [['footer_awal' => 40, 'footer_akhir' => 38], 'footer_akhir'],
]);

it('menolak kelompok yang bukan milik sheet tersebut (422)', function () {
    unggahMasterRab($this);
    $sheet = Sheet::first();

    $fileLain = FileExcel::create(['nama_file' => 'lain.xlsx', 'path' => 'x', 'hash_sha256' => str_repeat('b', 64)]);
    $sheetLain = Sheet::create(['file_excel_id' => $fileLain->id, 'kode_sheet' => '7733.001', 'nama_sheet' => 'RO Lain', 'urutan' => 1, 'ro_baris_awal' => 19, 'ro_baris_akhir' => 20, 'kolom_akhir' => 'X']);
    $kategoriLain = Kategori::create(['sheet_id' => $sheetLain->id, 'kode_kategori' => '051', 'nama_kategori' => 'K', 'baris_awal' => 21, 'baris_akhir' => 21, 'urutan' => 1]);
    $kelompokLain = Kelompok::create(['kategori_id' => $kategoriLain->id, 'kode_kelompok' => 'A', 'nama_kelompok' => 'Asli', 'baris_awal' => 22, 'baris_akhir' => 30, 'urutan' => 1]);

    koreksiRab($this, $sheet, ['nama' => [$kelompokLain->id => 'Disusupi']])->assertStatus(422);

    expect($kelompokLain->fresh()->nama_kelompok)->toBe('Asli');
});

it('memblokir non-admin mengoreksi sheet', function () {
    unggahMasterRab($this);
    $sheet = Sheet::first();

    $this->actingAs(userWithRole('tim_kerja', ['email' => 'tk-rab@test.local']))
        ->put(route('admin.rab-generator.sheet.update', $sheet), ['header_awal' => 1, 'header_akhir' => 18, 'footer_awal' => 36, 'footer_akhir' => 40])
        ->assertForbidden();
});

// --- Aktivasi ---

it('aktifkan menjadikan hanya satu file master aktif dan mencatat audit log', function () {
    unggahMasterRab($this);
    unggahMasterRab($this);
    [$pertama, $kedua] = FileExcel::orderBy('id')->get()->all();

    $this->actingAs($this->admin)->put(route('admin.rab-generator.aktifkan', $pertama))->assertRedirect();
    $this->actingAs($this->admin)->put(route('admin.rab-generator.aktifkan', $kedua))->assertRedirect();

    expect($pertama->fresh()->aktif)->toBeFalse()
        ->and($kedua->fresh()->aktif)->toBeTrue()
        ->and(FileExcel::aktif()->count())->toBe(1)
        ->and(FileExcel::count())->toBe(2); // file sebelumnya tetap tersimpan

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'mengaktifkan file master RAB'));
});

it('Q3: rumus total induk yang melewatkan anak disimpan sebagai peringatan sheet dan tampil di halaman tinjau', function () {
    unggahMasterRab($this, function (Spreadsheet $s) {
        $ws = $s->getSheetByName('7735.951');
        $ws->getCell('X21')->setValue('=X22'); // komponen 051 lupa sub B (baris 28)
        $ws->getCell('X22')->setValue(100);
        $ws->getCell('X28')->setValue(200);
    });
    $file = FileExcel::first();
    $pesan = 'Rumus total komponen 051 (baris 21) tidak menjumlahkan sub B (baris 28)';

    expect($file->peringatan['sheet']['7735.951'])->toHaveCount(1)
        ->and($file->peringatan['sheet']['7735.951'][0])->toContain($pesan)
        ->and($file->jumlah_peringatan)->toBe(2); // + sheet "Catatan" tanpa RO

    $this->actingAs($this->admin)->get(route('admin.rab-generator.show', $file))
        ->assertOk()->assertSee($pesan);
});

// --- Hapus file master lama yang tidak aktif ---

/** Dua master terunggah: [lama (tidak aktif), baru (aktif)]. */
function duaMasterRab($test): array
{
    unggahMasterRab($test);
    unggahMasterRab($test);
    [$lama, $baru] = FileExcel::orderBy('id')->get()->all();
    $baru->aktifkan();

    return [$lama->fresh(), $baru->fresh()];
}

it('hapus file master tidak aktif: baris, hasil deteksi (cascade), dan berkas di disk private hilang; log mencatat snapshot', function () {
    [$lama, $baru] = duaMasterRab($this);
    $path = $lama->path;
    $sheetBaru = Sheet::where('file_excel_id', $baru->id)->count();
    Storage::disk('private')->assertExists($path);

    $this->actingAs($this->admin)->delete(route('admin.rab-generator.destroy', $lama))
        ->assertRedirect(route('admin.rab-generator.index'))
        ->assertSessionHas('feedback.type', 'success');

    expect(FileExcel::find($lama->id))->toBeNull()
        ->and(Sheet::where('file_excel_id', $lama->id)->count())->toBe(0)
        ->and(Header::count())->toBe($sheetBaru)    // hanya milik master baru yang tersisa
        ->and(Footer::count())->toBe($sheetBaru)
        ->and(Kategori::count())->toBe(Kategori::whereIn('sheet_id', Sheet::pluck('id'))->count())
        ->and(Kelompok::count())->toBe(Kelompok::whereIn('kategori_id', Kategori::pluck('id'))->count());
    Storage::disk('private')->assertMissing($path);
    Storage::disk('private')->assertExists($baru->path);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => $e->description === 'menghapus file master RAB "master-uji.xlsx"'
        && $e->causer->is($this->admin)
        && $e->properties['path'] === $path
        && $e->properties['hash_sha256'] === $lama->hash_sha256
        && $e->properties['jumlah_sheet'] === 1
        && isset($e->properties['tanggal_upload']));
});

it('file master AKTIF tidak dapat dihapus: baris, berkas, dan hasil deteksi utuh, tanpa log', function () {
    [, $baru] = duaMasterRab($this);

    $this->actingAs($this->admin)->delete(route('admin.rab-generator.destroy', $baru))
        ->assertRedirect()->assertSessionHas('feedback.type', 'error');

    expect(FileExcel::find($baru->id))->not->toBeNull()
        ->and(Sheet::where('file_excel_id', $baru->id)->count())->toBeGreaterThan(0);
    Storage::disk('private')->assertExists($baru->path);
    Event::assertNotDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menghapus file master'));
});

it('file master yang baru diaktifkan di sela-sela tidak terhapus (penghapusan bersyarat atomik)', function () {
    [$lama] = duaMasterRab($this);
    $lama->aktifkan(); // diaktifkan sebelum permintaan hapus diproses

    $this->actingAs($this->admin)->delete(route('admin.rab-generator.destroy', $lama))
        ->assertSessionHas('feedback.type', 'error');

    expect(FileExcel::find($lama->id))->not->toBeNull();
    Storage::disk('private')->assertExists($lama->path);
});

it('hapus file master hanya untuk admin; flag mati 404; tamu ke halaman login; file utuh', function () {
    [$lama] = duaMasterRab($this);

    $this->actingAs(userWithRole('tim_kerja'))->delete(route('admin.rab-generator.destroy', $lama))->assertForbidden();

    auth()->logout();
    $this->delete(route('admin.rab-generator.destroy', $lama))->assertRedirect(route('login'));

    config(['rab_generator.enabled' => false]);
    $this->actingAs($this->admin)->delete(route('admin.rab-generator.destroy', $lama))->assertNotFound();

    expect(FileExcel::find($lama->id))->not->toBeNull();
    Storage::disk('private')->assertExists($lama->path);
});

it('daftar file master: tombol Hapus hanya pada file tidak aktif, dengan konfirmasi', function () {
    duaMasterRab($this);

    $html = $this->actingAs($this->admin)->get(route('admin.rab-generator.index'))->assertOk()->getContent();

    expect(substr_count($html, 'name="_method" value="DELETE"'))->toBe(1)
        ->and($html)->toContain('Hapus file master');
});

it('menghapus master lama tidak mengganggu pembuatan pratinjau dari master aktif', function () {
    [$lama, $baru] = duaMasterRab($this);

    $this->actingAs($this->admin)->delete(route('admin.rab-generator.destroy', $lama))->assertRedirect();

    expect(FileExcel::aktif()->first()->id)->toBe($baru->id)
        ->and(Storage::disk('private')->exists($baru->path))->toBeTrue();
});
