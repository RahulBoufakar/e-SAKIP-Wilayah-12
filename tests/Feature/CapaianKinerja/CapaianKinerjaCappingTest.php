<?php

use App\Models\CapaianKinerja;
use App\Models\Triwulan;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
});

it('menghitung persentase capaian normal ketika realisasi di bawah Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, ['target_pk' => 97.72]);
    $capaian = makeCapaianKinerja($iku, $this->tahun, ['realisasi' => 50]);

    // (50 / 97.72) x 100 = 51.17
    expect($capaian->capaian)->toBe(51.17);
});

it('capaian tetap dibatasi maksimal 100% sebagai jaring pengaman meski realisasi tersimpan melebihi Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, ['target_pk' => 97.72]);

    // Simulasikan data lama / hasil seeding yang lolos tanpa validasi baru.
    $capaian = makeCapaianKinerja($iku, $this->tahun, ['realisasi' => 150]);

    expect($capaian->capaian)->toBe(100.0);
});

it('mengembalikan null saat Target PK IKU nol atau tidak valid', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, ['target_pk' => 0]);

    $capaian = makeCapaianKinerja($iku, $this->tahun, ['realisasi' => 10]);

    expect($capaian->capaian)->toBeNull();
});

it('menolak dan menampilkan error saat realisasi manual (tanpa formula) melebihi Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, ['target_pk' => 97.72]); // tanpa formula_kode
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $triwulan = Triwulan::where('kode', 'TW1')->first();

    $response = $this->actingAs($user)->put(
        route('tim-kerja.capaian-kinerja.update', [$iku->id, $triwulan->id]),
        ['realisasi' => 150]
    );

    $response->assertSessionHasErrors('realisasi');

    $capaian = CapaianKinerja::where('iku_id', $iku->id)
        ->where('triwulan_id', $triwulan->id)
        ->first();

    expect($capaian->realisasi)->toBeNull(); // belum tersimpan karena ditolak validasi
});

it('menyimpan realisasi manual ketika masih di bawah atau sama dengan Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, ['target_pk' => 97.72]);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $triwulan = Triwulan::where('kode', 'TW1')->first();

    $response = $this->actingAs($user)->put(
        route('tim-kerja.capaian-kinerja.update', [$iku->id, $triwulan->id]),
        ['realisasi' => 97.72]
    );

    $response->assertSessionHasNoErrors();

    $capaian = CapaianKinerja::where('iku_id', $iku->id)
        ->where('triwulan_id', $triwulan->id)
        ->first();

    expect((float) $capaian->realisasi)->toBe(97.72);
});

it('menolak dan menampilkan error saat realisasi hasil formula melebihi Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, [
        'target_pk' => 90.0,
        'formula_kode' => 'iku_1_1_kepuasan', // (n / t) x 100
    ]);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $triwulan = Triwulan::where('kode', 'TW1')->first();

    // (5 / 5) x 100 = 100, melebihi Target PK 90.
    $response = $this->actingAs($user)->put(
        route('tim-kerja.capaian-kinerja.update', [$iku->id, $triwulan->id]),
        ['variabel' => ['n' => 5, 't' => 5]]
    );

    $response->assertSessionHasErrors('realisasi');

    $capaian = CapaianKinerja::where('iku_id', $iku->id)
        ->where('triwulan_id', $triwulan->id)
        ->first();

    expect($capaian->realisasi)->toBeNull();
});

it('menyimpan realisasi hasil formula ketika masih dalam batas Target PK', function () {
    $sasaran = makeSasaranKegiatan($this->tahun);
    $iku = makeIku($sasaran, [
        'target_pk' => 90.0,
        'formula_kode' => 'iku_1_1_kepuasan',
    ]);
    $timKerja = makeTimKerja();
    $iku->timKerja()->attach($timKerja->id);

    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($timKerja->id);

    $triwulan = Triwulan::where('kode', 'TW1')->first();

    // (4 / 5) x 100 = 80, di bawah Target PK 90.
    $response = $this->actingAs($user)->put(
        route('tim-kerja.capaian-kinerja.update', [$iku->id, $triwulan->id]),
        ['variabel' => ['n' => 4, 't' => 5]]
    );

    $response->assertSessionHasNoErrors();

    $capaian = CapaianKinerja::where('iku_id', $iku->id)
        ->where('triwulan_id', $triwulan->id)
        ->first();

    expect((float) $capaian->realisasi)->toBe(80.0);
});