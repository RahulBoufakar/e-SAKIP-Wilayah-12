<?php

use App\Models\LaporanKinerja;
use App\Models\Triwulan;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
});

it('tidak melacak updated_at karena bersifat append-only', function () {
    expect(LaporanKinerja::UPDATED_AT)->toBeNull();
});

it('menghasilkan label_periode sesuai jenis laporan', function () {
    $tw2 = Triwulan::where('kode', 'TW2')->first();

    $bulanan = makeLaporanKinerja($this->tahun, ['jenis' => 'bulanan', 'bulan' => 6]);
    $triwulanan = makeLaporanKinerja($this->tahun, ['jenis' => 'triwulanan', 'triwulan_id' => $tw2->id]);
    $tahunan = makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan']);

    expect($bulanan->label_periode)->toBe("Juni {$this->tahun->tahun}")
        ->and($triwulanan->label_periode)->toBe("Triwulan 2 {$this->tahun->tahun}")
        ->and($tahunan->label_periode)->toBe("Tahun {$this->tahun->tahun}");
});

it('menggabungkan label_periode dengan versi pada atribut label', function () {
    $laporan = makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'versi' => 3]);

    expect($laporan->label)->toBe("Tahun {$this->tahun->tahun} v3");
});

it('nextVersi() dimulai dari 1 untuk kombinasi jenis+periode yang baru', function () {
    expect(LaporanKinerja::nextVersi('tahunan', $this->tahun->id))->toBe(1);
});

it('nextVersi() bertambah untuk kombinasi jenis+periode yang sama', function () {
    makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'versi' => 1]);
    expect(LaporanKinerja::nextVersi('tahunan', $this->tahun->id))->toBe(2);

    makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'versi' => 2]);
    expect(LaporanKinerja::nextVersi('tahunan', $this->tahun->id))->toBe(3);
});

it('nextVersi() terpisah per bulan untuk jenis bulanan', function () {
    makeLaporanKinerja($this->tahun, ['jenis' => 'bulanan', 'bulan' => 1, 'versi' => 1]);

    expect(LaporanKinerja::nextVersi('bulanan', $this->tahun->id, 1))->toBe(2)
        ->and(LaporanKinerja::nextVersi('bulanan', $this->tahun->id, 2))->toBe(1);
});

it('nextVersi() terpisah per triwulan untuk jenis triwulanan', function () {
    $tw1 = Triwulan::where('kode', 'TW1')->value('id');
    $tw2 = Triwulan::where('kode', 'TW2')->value('id');

    makeLaporanKinerja($this->tahun, ['jenis' => 'triwulanan', 'triwulan_id' => $tw1, 'versi' => 1]);

    expect(LaporanKinerja::nextVersi('triwulanan', $this->tahun->id, null, $tw1))->toBe(2)
        ->and(LaporanKinerja::nextVersi('triwulanan', $this->tahun->id, null, $tw2))->toBe(1);
});

it('nextVersi() tidak tercampur antar jenis laporan yang berbeda', function () {
    makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'versi' => 5]);

    expect(LaporanKinerja::nextVersi('bulanan', $this->tahun->id, 1))->toBe(1);
});

it('nextVersi() tidak tercampur antar Tahun Anggaran yang berbeda', function () {
    $tahunLain = makeTahunAnggaran($this->tahun->tahun + 1);
    makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'versi' => 4]);

    expect(LaporanKinerja::nextVersi('tahunan', $tahunLain->id))->toBe(1);
});

it('relasi tahunAnggaran, triwulan, dan generatedBy resolve dengan benar', function () {
    $user = userWithRole('pimpinan');
    $tw3 = Triwulan::where('kode', 'TW3')->first();

    $laporan = makeLaporanKinerja($this->tahun, [
        'jenis' => 'triwulanan',
        'triwulan_id' => $tw3->id,
        'generated_by' => $user->id,
    ]);

    expect($laporan->tahunAnggaran->is($this->tahun))->toBeTrue()
        ->and($laporan->triwulan->is($tw3))->toBeTrue()
        ->and($laporan->generatedBy->is($user))->toBeTrue();
});

it('generated_by null berarti dibuat otomatis oleh sistem', function () {
    $laporan = makeLaporanKinerja($this->tahun, ['generated_by' => null]);

    expect($laporan->generatedBy)->toBeNull();
});