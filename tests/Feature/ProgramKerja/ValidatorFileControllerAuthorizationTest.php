<?php

use App\Models\LaporanKegiatan;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('private');
});

// --- AUDIT § B4: authorize() ditambahkan pada file-controller Validator.
// Test ini murni regression guard: memastikan penambahan authorize() TIDAK
// tiba-tiba memblokir akses sah yang sebelumnya berjalan (Validator boleh
// lihat dokumen dari Tim Kerja mana pun, bukan cuma "tim miliknya sendiri"
// seperti Tim Kerja). ---

it('validator tetap bisa preview & unduh file KAK Usulan Program Kerja dari tim manapun', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $validator = userWithRole('validator');

    $path = Storage::disk('private')->putFileAs(
        'usulan-program-kerja',
        UploadedFile::fake()->create('kak.pdf', 20, 'application/pdf'),
        'kak-b4.pdf'
    );
    $usulan = makeUsulan($iku, ['file_kak_pdf' => $path]);

    $this->actingAs($validator)
        ->get(route('validator.usulan-program-kerja.file.preview', [$usulan->id, 'kak']))
        ->assertOk();

    $this->actingAs($validator)
        ->get(route('validator.usulan-program-kerja.file.unduh', [$usulan->id, 'kak']))
        ->assertOk();
});

it('validator tetap bisa preview & unduh dokumen laporan kegiatan dari tim manapun', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $validator = userWithRole('validator');

    $usulan = makeUsulan($iku, ['status_validasi' => 'menunggu_validasi']);
    $usulan->setujui($validator->id);

    $laporan = LaporanKegiatan::create(['proker_id' => $usulan->fresh()->programKerja->id]);
    $path = Storage::disk('private')->putFileAs(
        'laporan-kegiatan',
        UploadedFile::fake()->create('sk.pdf', 20, 'application/pdf'),
        'sk-b4.pdf'
    );
    $dokumen = $laporan->dokumen()->create(['nama_dokumen' => 'SK Tim Monev', 'file_dokumen' => $path]);

    $this->actingAs($validator)
        ->get(route('validator.pelaporan-kegiatan.dokumen.preview', $dokumen->id))
        ->assertOk();

    $this->actingAs($validator)
        ->get(route('validator.pelaporan-kegiatan.dokumen.unduh', $dokumen->id))
        ->assertOk();
});

it('validator tetap bisa preview & unduh dokumen bukti Capaian Kinerja dari tim manapun', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $validator = userWithRole('validator');

    $capaian = makeCapaianKinerja($iku, $tahun);
    $path = Storage::disk('private')->putFileAs(
        'capaian-kinerja',
        UploadedFile::fake()->create('bukti.pdf', 20, 'application/pdf'),
        'bukti-b4.pdf'
    );
    $dokumen = $capaian->dokumen()->create(['nama_dokumen' => 'Bukti Uji', 'file_dokumen' => $path]);

    $this->actingAs($validator)
        ->get(route('validator.capaian-kinerja.dokumen.preview', $dokumen->id))
        ->assertOk();

    $this->actingAs($validator)
        ->get(route('validator.capaian-kinerja.dokumen.unduh', $dokumen->id))
        ->assertOk();
});
