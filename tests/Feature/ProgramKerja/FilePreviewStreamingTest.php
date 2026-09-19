<?php

use App\Models\LaporanKegiatan;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
    Storage::fake('public');
});

/** Ambil isi StreamedResponse penuh untuk dibandingkan dengan file asli. */
function streamedBody($response): string
{
    ob_start();
    $response->baseResponse->sendContent();

    return ob_get_clean();
}

it('preview KAK Usulan Program Kerja (TimKerja) di-stream sebagai binary, bukan JSON base64', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $content = 'ISI-PDF-DUMMY-KAK-TIMKERJA';
    $path = 'usulan-program-kerja/kak-stream-timkerja.pdf';
    Storage::disk('private')->put($path, $content);
    $usulan = makeUsulan($iku, ['file_kak_pdf' => $path]);

    $response = $this->actingAs($user)->get(route('tim-kerja.usulan-program-kerja.file.preview', [$usulan->id, 'kak']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview KAK Usulan Program Kerja (Validator) di-stream sebagai binary', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $validator = userWithRole('validator');

    $content = 'ISI-PDF-DUMMY-KAK-VALIDATOR';
    $path = 'usulan-program-kerja/kak-stream-validator.pdf';
    Storage::disk('private')->put($path, $content);
    $usulan = makeUsulan($iku, ['file_kak_pdf' => $path]);

    $response = $this->actingAs($validator)->get(route('validator.usulan-program-kerja.file.preview', [$usulan->id, 'kak']));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview dokumen laporan kegiatan (TimKerja) di-stream sebagai binary', function () {
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

    $content = 'ISI-PDF-DUMMY-SK-TIMKERJA';
    $path = 'laporan-kegiatan/sk-stream-timkerja.pdf';
    Storage::disk('private')->put($path, $content);
    $laporan = LaporanKegiatan::create(['proker_id' => $usulan->fresh()->programKerja->id]);
    $dokumen = $laporan->dokumen()->create(['nama_dokumen' => 'SK Tim Monev', 'file_dokumen' => $path]);

    $response = $this->actingAs($user)->get(route('tim-kerja.pelaporan-kegiatan.dokumen.preview', $dokumen->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview dokumen laporan kegiatan (Validator) di-stream sebagai binary', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $validator = userWithRole('validator');

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $usulan->setujui($validator->id);

    $content = 'ISI-PDF-DUMMY-SK-VALIDATOR';
    $path = 'laporan-kegiatan/sk-stream-validator.pdf';
    Storage::disk('private')->put($path, $content);
    $laporan = LaporanKegiatan::create(['proker_id' => $usulan->fresh()->programKerja->id]);
    $dokumen = $laporan->dokumen()->create(['nama_dokumen' => 'SK Tim Monev', 'file_dokumen' => $path]);

    $response = $this->actingAs($validator)->get(route('validator.pelaporan-kegiatan.dokumen.preview', $dokumen->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview dokumen bukti Capaian Kinerja (TimKerja) di-stream sebagai binary', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $capaian = makeCapaianKinerja($iku, $tahun);
    $content = 'ISI-PDF-DUMMY-BUKTI-TIMKERJA';
    $path = 'capaian-kinerja/bukti-stream-timkerja.pdf';
    Storage::disk('private')->put($path, $content);
    $dokumen = $capaian->dokumen()->create(['nama_dokumen' => 'Bukti Uji', 'file_dokumen' => $path]);

    $response = $this->actingAs($user)->get(route('tim-kerja.capaian-kinerja.dokumen.preview', $dokumen->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview dokumen bukti Capaian Kinerja (Validator) di-stream sebagai binary', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $validator = userWithRole('validator');

    $capaian = makeCapaianKinerja($iku, $tahun);
    $content = 'ISI-PDF-DUMMY-BUKTI-VALIDATOR';
    $path = 'capaian-kinerja/bukti-stream-validator.pdf';
    Storage::disk('private')->put($path, $content);
    $dokumen = $capaian->dokumen()->create(['nama_dokumen' => 'Bukti Uji', 'file_dokumen' => $path]);

    $response = $this->actingAs($validator)->get(route('validator.capaian-kinerja.dokumen.preview', $dokumen->id));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('preview Template Dokumen (Admin) ikut di-stream sebagai binary — konsisten dengan komponen x-file-preview yang sama', function () {
    $admin = userWithRole('admin');
    (new \Database\Seeders\TemplateDokumenSeeder())->run();

    $content = 'ISI-PDF-DUMMY-TEMPLATE-RAB';
    $path = 'template-dokumen/rab-stream.pdf';
    Storage::disk('public')->put($path, $content);
    \App\Models\TemplateDokumen::where('kode', 'rab_pdf')->update(['file' => $path]);

    $response = $this->actingAs($admin)->get(route('admin.pengaturan.template.preview', 'rab_pdf'));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toStartWith('application/pdf')
        ->and($response->headers->has('Content-Disposition'))->toBeFalse()
        ->and(streamedBody($response))->toBe($content);
});

it('unduh() tetap memaksa download (Content-Disposition attachment), tidak ikut berubah jadi inline', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $path = 'usulan-program-kerja/kak-unduh.pdf';
    Storage::disk('private')->put($path, 'ISI-PDF-UNDUH');
    $usulan = makeUsulan($iku, ['file_kak_pdf' => $path]);

    $response = $this->actingAs($user)->get(route('tim-kerja.usulan-program-kerja.file.unduh', [$usulan->id, 'kak']));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});
