<?php

use App\Models\DetailKegiatan;

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $this->iku = makeIku($sasaran);
});

it('false saat file belum lengkap', function () {
    $usulan = makeUsulan($this->iku);

    expect($usulan->can_kirim)->toBeFalse();
});

it('false saat file lengkap tapi detail kegiatan belum ada', function () {
    $usulan = makeUsulan($this->iku, [
        'file_kak_pdf' => 'kak.pdf',
        'file_rab_pdf' => 'rab.pdf',
        'file_rab_excel' => 'rab.xlsx',
    ]);

    expect($usulan->can_kirim)->toBeFalse();
});

it('true hanya saat file, detail kegiatan, dan status semuanya terpenuhi', function () {
    $usulan = makeUsulan($this->iku, [
        'file_kak_pdf' => 'kak.pdf',
        'file_rab_pdf' => 'rab.pdf',
        'file_rab_excel' => 'rab.xlsx',
    ]);

    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Uji',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'bulan_kegiatan' => [1, 2],
        'anggaran' => 1000000,
    ]);

    expect($usulan->fresh()->can_kirim)->toBeTrue();
});

it('false saat status sudah menunggu_validasi meskipun data lengkap', function () {
    $usulan = makeUsulan($this->iku, [
        'file_kak_pdf' => 'kak.pdf',
        'file_rab_pdf' => 'rab.pdf',
        'file_rab_excel' => 'rab.xlsx',
        'status_validasi' => 'menunggu_validasi',
    ]);

    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Uji',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'bulan_kegiatan' => [1, 2],
        'anggaran' => 1000000,
    ]);

    expect($usulan->fresh()->can_kirim)->toBeFalse();
});