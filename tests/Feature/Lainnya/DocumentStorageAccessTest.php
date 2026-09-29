<?php

use App\Models\LaporanKegiatan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

it('menyimpan dokumen bukti Capaian Kinerja ke disk private, bukan public', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    activateTriwulan($tahun, 'TW1');
    $capaian = makeCapaianKinerja($iku, $tahun);

    $response = $this->actingAs($user)->post(route('tim-kerja.capaian-kinerja.baris.store', [
        'iku' => $iku->id,
        'komponen' => 'utama',
        'triwulan_id' => $capaian->triwulan_id,
    ]), [
        'total_responden' => 10,
        'responden_puas' => 9,
        'file_bukti_dukung' => UploadedFile::fake()->create('bukti.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();

    $baris = $capaian->kepuasanLayanan()->first();

    expect($baris)->not->toBeNull()
        ->and($baris->file_bukti_dukung)->not->toBeNull();
    Storage::disk('private')->assertExists($baris->file_bukti_dukung);
    Storage::disk('public')->assertMissing($baris->file_bukti_dukung);
});

it('endpoint preview Capaian Kinerja tetap bisa membaca file dari disk private bagi yang berwenang', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $capaian = makeCapaianKinerja($iku, $tahun);
    $path = Storage::disk('private')->putFileAs(
        'capaian-kinerja-hybrid',
        UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        'bukti-uji.pdf'
    );
    $baris = $capaian->kepuasanLayanan()->create([
        'total_responden' => 10,
        'responden_puas' => 9,
        'status_validasi' => 'draft',
        'file_bukti_dukung' => $path,
    ]);

    $response = $this->actingAs($user)->get(route('tim-kerja.capaian-kinerja.bukti.preview', [
        'iku' => $iku->id,
        'komponen' => 'utama',
        'barisId' => $baris->id,
        'triwulan_id' => $capaian->triwulan_id,
    ]));

    $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
});

it('menyimpan file KAK/RAB Usulan Program Kerja ke disk private saat diunggah', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $usulan = makeUsulan($iku);

    $response = $this->actingAs($user)->put(route('tim-kerja.usulan-program-kerja.update', $usulan->id), [
        'nama_usulan' => $usulan->nama_usulan,
        'file_kak_pdf' => UploadedFile::fake()->create('kak.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();

    $path = $usulan->fresh()->file_kak_pdf;

    expect($path)->not->toBeNull();
    Storage::disk('private')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

it('endpoint preview/unduh KAK Usulan Program Kerja tetap bisa membaca dari disk private', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $path = Storage::disk('private')->putFileAs(
        'usulan-program-kerja',
        UploadedFile::fake()->create('kak.pdf', 50, 'application/pdf'),
        'kak-uji.pdf'
    );
    $usulan = makeUsulan($iku, ['file_kak_pdf' => $path]);

    $previewResponse = $this->actingAs($user)->get(route('tim-kerja.usulan-program-kerja.file.preview', [$usulan->id, 'kak']));
    $unduhResponse = $this->actingAs($user)->get(route('tim-kerja.usulan-program-kerja.file.unduh', [$usulan->id, 'kak']));

    $previewResponse->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $unduhResponse->assertOk();
});

it('menyimpan dokumen laporan kegiatan ke disk private saat diunggah', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');
    $usulan->setujui($validator->id);

    $laporan = LaporanKegiatan::create(['proker_id' => $usulan->fresh()->programKerja->id]);
    $dokumen = $laporan->dokumen()->create(['nama_dokumen' => 'SK Tim Monev']);

    $response = $this->actingAs($user)->put(route('tim-kerja.pelaporan-kegiatan.dokumen.upload', $dokumen->id), [
        'file_dokumen' => UploadedFile::fake()->create('sk.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect();

    $path = $dokumen->fresh()->file_dokumen;

    expect($path)->not->toBeNull();
    Storage::disk('private')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
});

it('menghapus file lama dari disk private (bukan public) saat dokumen capaian kinerja dihapus', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran, ['tipe_iku' => 'kepuasan_layanan']);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    activateTriwulan($tahun, 'TW1');
    $capaian = makeCapaianKinerja($iku, $tahun);
    $path = Storage::disk('private')->putFileAs(
        'capaian-kinerja-hybrid',
        UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'),
        'bukti-hapus.pdf'
    );
    $baris = $capaian->kepuasanLayanan()->create([
        'total_responden' => 10,
        'responden_puas' => 9,
        'status_validasi' => 'draft',
        'file_bukti_dukung' => $path,
    ]);

    $this->actingAs($user)->delete(route('tim-kerja.capaian-kinerja.baris.destroy', [
        'iku' => $iku->id,
        'komponen' => 'utama',
        'barisId' => $baris->id,
        'triwulan_id' => $capaian->triwulan_id,
    ]));

    Storage::disk('private')->assertMissing($path);
    $this->assertDatabaseMissing('capaian_kepuasan_layanan', ['id' => $baris->id]);
});

it('command documents:migrate-to-private memindahkan file lama dari public ke private tanpa mengubah path di database', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    // Simulasikan data lama: file masih ada di disk public (skenario sebelum perbaikan A1).
    $path = 'usulan-program-kerja/kak-lama.pdf';
    Storage::disk('public')->put($path, 'dummy-pdf-content');
    $usulan->update(['file_kak_pdf' => $path]);

    $this->artisan('documents:migrate-to-private')->assertSuccessful();

    Storage::disk('private')->assertExists($path);
    Storage::disk('public')->assertMissing($path);
    expect($usulan->fresh()->file_kak_pdf)->toBe($path);
});

it('command documents:migrate-to-private aman dijalankan dua kali (idempoten)', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    $path = 'usulan-program-kerja/kak-lama-2.pdf';
    Storage::disk('public')->put($path, 'dummy-pdf-content');
    $usulan->update(['file_kak_pdf' => $path]);

    $this->artisan('documents:migrate-to-private')->assertSuccessful();
    $this->artisan('documents:migrate-to-private')->assertSuccessful(); // dijalankan lagi, tidak boleh error

    Storage::disk('private')->assertExists($path);
});

it('dry-run pada command migrasi tidak benar-benar memindahkan file', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    $path = 'usulan-program-kerja/kak-dry-run.pdf';
    Storage::disk('public')->put($path, 'dummy-pdf-content');
    $usulan->update(['file_kak_pdf' => $path]);

    $this->artisan('documents:migrate-to-private', ['--dry-run' => true])->assertSuccessful();

    Storage::disk('public')->assertExists($path);
    Storage::disk('private')->assertMissing($path);
});
