<?php

beforeEach(function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $this->iku = makeIku($sasaran); // kode iku: [iku 1.1]
});

it('menurunkan kode_proker dari nomor kode IKU dan urutan dimulai dari 1', function () {
    $validator = userWithRole('validator');
    $usulan = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);

    $usulan->setujui($validator->id);

    expect($usulan->programKerja->kode_proker)->toBe('1.1.1');
});

it('menaikkan urutan untuk usulan kedua yang disetujui pada IKU & tahun yang sama', function () {
    $validator = userWithRole('validator');

    $pertama = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi']);
    $pertama->setujui($validator->id);

    $kedua = makeUsulan($this->iku, ['status_validasi' => 'menunggu_validasi', 'nama_usulan' => 'Usulan Kedua']);
    $kedua->setujui($validator->id);

    expect($kedua->programKerja->kode_proker)->toBe('1.1.2');
});

// --- AUDIT § A5.2: lock generator kode — verifikasi struktural (bukan true-concurrency) ---

it('menghasilkan kode_proker yang selalu unik dan berurutan saat beberapa usulan disetujui berturut-turut cepat pada IKU & tahun yang sama', function () {
    $validator = userWithRole('validator');

    $kodeList = [];
    for ($i = 1; $i <= 8; $i++) {
        $usulan = makeUsulan($this->iku, [
            'status_validasi' => 'menunggu_validasi',
            'nama_usulan' => "Usulan ke-{$i}",
        ]);
        $usulan->setujui($validator->id);
        $kodeList[] = $usulan->programKerja->kode_proker;
    }

    expect($kodeList)->toBe([
        '1.1.1', '1.1.2', '1.1.3', '1.1.4', '1.1.5', '1.1.6', '1.1.7', '1.1.8',
    ])->and(count(array_unique($kodeList)))->toBe(8);
});
