<?php

use App\Jobs\GenerateLaporanKinerjaJob;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
});

it('langsung berhenti tanpa error saat record Laporan Kinerja sudah tidak ada', function () {
    (new GenerateLaporanKinerjaJob(999999))->handle();

    expect(true)->toBeTrue();
});

it('menandai laporan berstatus gagal dan menyimpan catatan saat data periode tidak konsisten', function () {
    // triwulan_id null padahal jenis = triwulanan -> Triwulan::findOrFail(null) melempar
    // exception di dalam job. Ini menguji jalur catch(Throwable) terlepas dari apakah
    // package PDF (barryvdh/laravel-dompdf) sudah terpasang atau belum.
    $laporan = makeLaporanKinerja($this->tahun, [
        'jenis' => 'triwulanan',
        'triwulan_id' => null,
        'status' => 'diproses',
    ]);

    (new GenerateLaporanKinerjaJob($laporan->id))->handle();

    $laporan->refresh();

    expect($laporan->status)->toBe('gagal')
        ->and($laporan->catatan)->not->toBeNull();
});

it('merender PDF, menyimpan file, dan mengirim notifikasi hanya untuk laporan otomatis', function () {
    Storage::fake('laporan');
    userWithRole('pimpinan');
    userWithRole('admin', ['email' => 'admin-job@test.local']);

    $laporan = makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'status' => 'diproses', 'generated_by' => null]);

    (new GenerateLaporanKinerjaJob($laporan->id))->handle();

    $laporan->refresh();

    expect($laporan->status)->toBe('berhasil')
        ->and($laporan->file_path)->not->toBeNull();

    Storage::disk('laporan')->assertExists($laporan->file_path);
    $this->assertDatabaseCount('notifications', 2);
})->skip(fn () => ! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 'Butuh package barryvdh/laravel-dompdf terpasang (lihat NOTES.md).');

it('laporan hasil generate manual tidak mengirim notifikasi meski job selesai berhasil', function () {
    Storage::fake('laporan');
    $admin = userWithRole('admin', ['email' => 'admin-job2@test.local']);

    $laporan = makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan', 'status' => 'diproses', 'generated_by' => $admin->id]);

    (new GenerateLaporanKinerjaJob($laporan->id))->handle();

    expect($laporan->fresh()->status)->toBe('berhasil');
    $this->assertDatabaseCount('notifications', 0);
})->skip(fn () => ! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 'Butuh package barryvdh/laravel-dompdf terpasang (lihat NOTES.md).');