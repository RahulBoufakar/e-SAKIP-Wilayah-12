<?php

use App\Models\DetailKegiatan;
use Illuminate\Database\QueryException;

it('hanya mengizinkan satu detail kegiatan per usulan program kerja (unique constraint)', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Pertama',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'tanggal_mulai' => '2026-01-05', 'tanggal_selesai' => '2026-01-20',
        'anggaran' => 500000,
    ]);

    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Kedua',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Daring',
        'tanggal_mulai' => '2026-02-02', 'tanggal_selesai' => '2026-02-20',
        'anggaran' => 750000,
    ]);
})->throws(QueryException::class);

it('menurunkan bulan_kegiatan dari rentang tanggal dan anggaran menjadi decimal 2 digit', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    $detail = DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Uji',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'tanggal_mulai' => '2026-03-03', 'tanggal_selesai' => '2026-05-15',
        'anggaran' => 1250000.5,
    ]);

    expect($detail->fresh()->bulan_kegiatan)->toBe([3, 4, 5])
        ->and($detail->fresh()->anggaran)->toBe('1250000.50');
});
// Form Detail Kegiatan: Bulan Kegiatan diisi lewat rentang tanggal mulai - selesai.
function detailKegiatanSetup(): array
{
    $tim = makeTimKerja();
    $iku = makeIku(makeSasaranKegiatan(makeTahunAnggaran()));
    $iku->timKerja()->attach($tim->id);
    $user = userWithRole('tim_kerja');
    $user->timKerja()->attach($tim->id);

    return [$user, makeUsulan($iku)];
}

function detailKegiatanPayload(array $override = []): array
{
    return array_merge([
        'nama_detail' => 'Detail Uji',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Hybrid',
        'tanggal_mulai' => '2026-03-03',
        'tanggal_selesai' => '2026-05-15',
        'anggaran' => 1000000,
    ], $override);
}

it('menyimpan rentang tanggal dan bulan kegiatan terisi otomatis', function () {
    [$user, $usulan] = detailKegiatanSetup();

    $this->actingAs($user)
        ->put(route('tim-kerja.usulan-program-kerja.detail.store-or-update', $usulan), detailKegiatanPayload())
        ->assertSessionHasNoErrors();

    $detail = $usulan->fresh()->detailKegiatan;
    expect($detail->tanggal_mulai->toDateString())->toBe('2026-03-03')
        ->and($detail->tanggal_selesai->toDateString())->toBe('2026-05-15')
        ->and($detail->bulan_kegiatan)->toBe([3, 4, 5]);
});

it('menolak tanggal di luar tahun usulan', function (array $override, string $field) {
    [$user, $usulan] = detailKegiatanSetup();

    $this->actingAs($user)
        ->put(route('tim-kerja.usulan-program-kerja.detail.store-or-update', $usulan), detailKegiatanPayload($override))
        ->assertSessionHasErrors($field);

    expect($usulan->fresh()->detailKegiatan)->toBeNull();
})->with([
    'mulai tahun lalu' => [['tanggal_mulai' => '2025-12-31'], 'tanggal_mulai'],
    'selesai tahun depan' => [['tanggal_selesai' => '2027-01-01'], 'tanggal_selesai'],
]);

it('menolak tanggal selesai sebelum tanggal mulai', function () {
    [$user, $usulan] = detailKegiatanSetup();

    $this->actingAs($user)
        ->put(route('tim-kerja.usulan-program-kerja.detail.store-or-update', $usulan),
            detailKegiatanPayload(['tanggal_mulai' => '2026-05-15', 'tanggal_selesai' => '2026-03-03']))
        ->assertSessionHasErrors('tanggal_selesai');
});
