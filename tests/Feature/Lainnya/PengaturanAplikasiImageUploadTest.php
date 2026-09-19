<?php

use App\Models\PengaturanAplikasi;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

beforeEach(function () {
    Storage::fake('public');
    $this->admin = userWithRole('admin');
});

it('admin dapat mengunggah logo: diresize max-width 400px dan dikonversi ke WebP', function () {
    $logo = UploadedFile::fake()->image('logo.png', 800, 400);

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);

    $response->assertRedirect();
    $pengaturan = PengaturanAplikasi::current()->fresh();

    expect($pengaturan->logo)->toEndWith('.webp');
    Storage::disk('public')->assertExists($pengaturan->logo);

    $manager = new ImageManager(['driver' => 'gd']);
    $result = $manager->make(Storage::disk('public')->path($pengaturan->logo));

    expect($result->width())->toBe(400)
        ->and($result->height())->toBe(200); // rasio 800:400 dipertahankan
});

it('logo yang lebih kecil dari 400px tidak di-upscale', function () {
    $logo = UploadedFile::fake()->image('logo-kecil.png', 200, 100);

    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);

    $pengaturan = PengaturanAplikasi::current()->fresh();

    $manager = new ImageManager(['driver' => 'gd']);
    $result = $manager->make(Storage::disk('public')->path($pengaturan->logo));

    expect($result->width())->toBe(200)
        ->and($result->height())->toBe(100);
});

it('menolak logo dengan format yang tidak didukung', function () {
    $logo = UploadedFile::fake()->create('logo.gif', 100, 'image/gif');

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);

    $response->assertSessionHasErrors('logo');
    expect(PengaturanAplikasi::current()->fresh()->logo)->toBeNull();
});

it('menolak logo yang melebihi ukuran maksimal 2 MB', function () {
    $logo = UploadedFile::fake()->image('logo-besar.png')->size(3000);

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);

    $response->assertSessionHasErrors('logo');
});

it('admin dapat mengunggah favicon PNG: di-crop-resize persis 32x32 dan dikonversi ke PNG', function () {
    // AUDIT § A3: favicon raster kini hanya menerima PNG (bukan JPG lagi).
    $favicon = UploadedFile::fake()->image('favicon-source.png', 100, 50);

    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $pengaturan = PengaturanAplikasi::current()->fresh();

    expect($pengaturan->favicon)->toEndWith('.png');
    Storage::disk('public')->assertExists($pengaturan->favicon);

    $manager = new ImageManager(['driver' => 'gd']);
    $result = $manager->make(Storage::disk('public')->path($pengaturan->favicon));

    expect($result->width())->toBe(32)
        ->and($result->height())->toBe(32);
});

it('favicon ICO disimpan apa adanya tanpa diproses', function () {
    $favicon = UploadedFile::fake()->create('favicon.ico', 5, 'image/vnd.microsoft.icon');

    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $pengaturan = PengaturanAplikasi::current()->fresh();

    expect($pengaturan->favicon)->toEndWith('.ico');
    Storage::disk('public')->assertExists($pengaturan->favicon);
});

it('menolak favicon yang melebihi ukuran maksimal 1 MB', function () {
    $favicon = UploadedFile::fake()->create('favicon.png', 1200, 'image/png');

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $response->assertSessionHasErrors('favicon');
});

it('menghapus file logo lama dari storage setelah logo baru berhasil diproses', function () {
    $logoLama = UploadedFile::fake()->image('logo-lama.png', 300, 150);
    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logoLama,
    ]);
    $pathLogoLama = PengaturanAplikasi::current()->fresh()->logo;
    Storage::disk('public')->assertExists($pathLogoLama);

    // Bawa waktu Laravel maju 1 detik agar timestamp berubah
    $this->travel(1)->seconds();

    $logoBaru = UploadedFile::fake()->image('logo-baru.png', 500, 250);
    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logoBaru,
    ]);
    $pathLogoBaru = PengaturanAplikasi::current()->fresh()->logo;

    expect($pathLogoBaru)->not->toBe($pathLogoLama);
    Storage::disk('public')->assertMissing($pathLogoLama);
    Storage::disk('public')->assertExists($pathLogoBaru);
});

it('tidak menghapus logo lama jika tidak ada file logo baru pada request', function () {
    $logo = UploadedFile::fake()->image('logo.png', 300, 150);
    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);
    $path = PengaturanAplikasi::current()->fresh()->logo;

    // Update lain tanpa menyertakan field logo sama sekali
    $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'Nama Baru Tanpa Ganti Logo',
    ]);

    expect(PengaturanAplikasi::current()->fresh()->logo)->toBe($path);
    Storage::disk('public')->assertExists($path);
});

it('memblokir role selain admin mengakses update pengaturan aplikasi', function () {
    $validator = userWithRole('validator');
    $logo = UploadedFile::fake()->image('logo.png', 300, 150);

    $response = $this->actingAs($validator)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'logo' => $logo,
    ]);

    $response->assertForbidden();
});

// --- AUDIT § A3: SVG dilarang total, tambah validasi dimensi ---

it('menolak upload SVG untuk favicon karena hanya PNG/ICO yang didukung', function () {
    $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32"><circle cx="16" cy="16" r="16"/></svg>';
    $favicon = UploadedFile::fake()->createWithContent('favicon.svg', $svgContent);

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $response->assertSessionHasErrors('favicon');
    expect(PengaturanAplikasi::current()->fresh()->favicon)->toBeNull();
});

it('menolak favicon PNG dengan dimensi melebihi batas maksimal 512x512px', function () {
    $favicon = UploadedFile::fake()->image('favicon-raksasa.png', 2000, 2000);

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $response->assertRedirect();
    expect(session('feedback')['type'])->toBe('error')
        ->and(session('feedback')['message'])->toContain('512')
        ->and(PengaturanAplikasi::current()->fresh()->favicon)->toBeNull();
});

it('menerima favicon PNG persis pada batas 512x512px', function () {
    $favicon = UploadedFile::fake()->image('favicon-batas.png', 512, 512);

    $response = $this->actingAs($this->admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'eSAKIP LLDikti',
        'favicon' => $favicon,
    ]);

    $response->assertRedirect();
    expect(session('feedback')['type'])->toBe('success');
    Storage::disk('public')->assertExists(PengaturanAplikasi::current()->fresh()->favicon);
});
