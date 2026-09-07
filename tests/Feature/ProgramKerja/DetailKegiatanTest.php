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
        'bulan_kegiatan' => [1],
        'anggaran' => 500000,
    ]);

    DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Kedua',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Daring',
        'bulan_kegiatan' => [2],
        'anggaran' => 750000,
    ]);
})->throws(QueryException::class);

it('meng-cast bulan_kegiatan menjadi array dan anggaran menjadi decimal 2 digit', function () {
    $tahun = makeTahunAnggaran();
    $sasaran = makeSasaranKegiatan($tahun);
    $iku = makeIku($sasaran);
    $usulan = makeUsulan($iku);

    $detail = DetailKegiatan::create([
        'usulan_program_kerja_id' => $usulan->id,
        'nama_detail' => 'Detail Uji',
        'tempat_pelaksanaan' => 'Kantor',
        'bentuk_kegiatan' => 'Luring',
        'bulan_kegiatan' => [3, 4, 5],
        'anggaran' => 1250000.5,
    ]);

    expect($detail->fresh()->bulan_kegiatan)->toBe([3, 4, 5])
        ->and($detail->fresh()->anggaran)->toBe('1250000.50');
});