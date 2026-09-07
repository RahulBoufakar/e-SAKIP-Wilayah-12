<?php

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($this->tahun);
    $this->iku = makeIku($sasaran);
});

it('berpindah dari draft ke menunggu_validasi saat kirim() dipanggil', function () {
    $usulan = makeUsulan($this->iku);

    $usulan->kirim();

    expect($usulan->status_validasi)->toBe('menunggu_validasi');
});

it('menolak kirim() kalau status bukan draft atau rejected', function () {
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);

    $usulan->kirim();
})->throws(RuntimeException::class);

it('menyetujui usulan dan otomatis membuat baris program_kerja', function () {
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');

    $usulan->setujui($validator->id);

    expect($usulan->status_validasi)->toBe('approved')
        ->and($usulan->validator_id)->toBe($validator->id)
        ->and($usulan->programKerja)->not->toBeNull();
});

it('menolak usulan hanya dari status menunggu_validasi dan mewajibkan catatan_revisi', function () {
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $validator = userWithRole('validator');

    $usulan->tolak($validator->id, 'Kurang lengkap');

    expect($usulan->status_validasi)->toBe('rejected')
        ->and($usulan->catatan_revisi)->toBe('Kurang lengkap');
});

it('melempar error saat tolak() dipanggil dengan catatan_revisi kosong', function () {
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);

    $usulan->tolak(1, '   ');
})->throws(InvalidArgumentException::class);

it('mengunci usulan saat menunggu_validasi atau approved', function () {
    $menunggu = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $approved = makeUsulan($this->iku, ['status_validasi' => 'approved']);
    $draft = makeUsulan($this->iku, ['status_validasi' => 'draft']);

    expect($menunggu->isFieldLocked())->toBeTrue()
        ->and($approved->isFieldLocked())->toBeTrue()
        ->and($draft->isFieldLocked())->toBeFalse();
});

it('menolak simpan() saat usulan sedang terkunci', function () {
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);

    $usulan->simpan(['nama_usulan' => 'Diubah paksa']);
})->throws(RuntimeException::class);