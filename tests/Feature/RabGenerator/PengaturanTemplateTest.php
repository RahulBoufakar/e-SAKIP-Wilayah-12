<?php

use App\Models\FileExcel;
use App\Models\TemplateDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/*
 * Tab "Template Dokumen" di Pengaturan (08 langkah 12 butir 3): kartu unggah rab_excel lama disembunyikan
 * begitu RAB Generator menggantikannya, dan diganti kartu yang mengarah ke halaman file master.
 * Template PDF dan KAK/TOR tidak berubah.
 */

function halamanTemplate($test)
{
    return $test->actingAs($test->admin)->get(route('admin.pengaturan.index', ['tab' => 'template']));
}

function aksiUnggah(string $kode): string
{
    return 'action="'.route('admin.pengaturan.template.update', $kode).'"';
}

beforeEach(function () {
    $this->admin = userWithRole('admin');

    foreach (['rab_excel' => 'Template RAB (Excel)', 'rab_pdf' => 'Template RAB (PDF)', 'kak_tor' => 'Template KAK/TOR (Word)'] as $kode => $nama) {
        TemplateDokumen::create(['kode' => $kode, 'nama' => $nama]);
    }
});

it('flag mati: tampilan lama utuh, kartu rab_excel masih ada dan tidak ada tombol generator', function () {
    config(['rab_generator.enabled' => false]);

    halamanTemplate($this)->assertOk()
        ->assertSee(aksiUnggah('rab_excel'), false)
        ->assertSee(aksiUnggah('rab_pdf'), false)
        ->assertSee(aksiUnggah('kak_tor'), false)
        ->assertDontSee('Kelola File Master RAB');
});

it('flag aktif tanpa master aktif: tombol ke generator tampil dengan peringatan, kartu rab_excel lama tetap ada', function () {
    config(['rab_generator.enabled' => true]);

    halamanTemplate($this)->assertOk()
        ->assertSee('Kelola File Master RAB')
        ->assertSee(route('admin.rab-generator.index'), false)
        ->assertSee('Belum ada file master aktif')
        ->assertSee(aksiUnggah('rab_excel'), false); // template lama masih satu-satunya yang dipakai tim kerja
});

it('flag aktif dengan master aktif: kartu rab_excel lama disembunyikan, PDF dan KAK/TOR tetap, master aktif ditampilkan', function () {
    config(['rab_generator.enabled' => true]);
    FileExcel::create(['nama_file' => 'master-rab-2026.xlsx', 'path' => 'rab-master/x.xlsx', 'hash_sha256' => str_repeat('a', 64)])->aktifkan();

    halamanTemplate($this)->assertOk()
        ->assertSee('Kelola File Master RAB')
        ->assertSee('File master aktif')
        ->assertSee('master-rab-2026.xlsx')
        ->assertDontSee(aksiUnggah('rab_excel'), false)
        ->assertSee(aksiUnggah('rab_pdf'), false)
        ->assertSee(aksiUnggah('kak_tor'), false);
});

it('master tidak aktif tidak dianggap menggantikan template lama', function () {
    config(['rab_generator.enabled' => true]);
    FileExcel::create(['nama_file' => 'draf.xlsx', 'path' => 'rab-master/d.xlsx', 'hash_sha256' => str_repeat('b', 64)]);

    halamanTemplate($this)->assertOk()
        ->assertSee('Belum ada file master aktif')
        ->assertSee(aksiUnggah('rab_excel'), false);
});

it('endpoint unggah template rab_excel tetap berfungsi walau kartunya disembunyikan (rute dan data tidak dihapus)', function () {
    Storage::fake('public');
    config(['rab_generator.enabled' => true]);
    FileExcel::create(['nama_file' => 'm.xlsx', 'path' => 'rab-master/m.xlsx', 'hash_sha256' => str_repeat('c', 64)])->aktifkan();

    $this->actingAs($this->admin)
        ->put(route('admin.pengaturan.template.update', 'rab_excel'), ['file' => UploadedFile::fake()->create('lama.xlsx', 10)])
        ->assertRedirect();

    $template = TemplateDokumen::where('kode', 'rab_excel')->first();

    expect($template->file)->not->toBeNull();
    Storage::disk('public')->assertExists($template->file);
});
