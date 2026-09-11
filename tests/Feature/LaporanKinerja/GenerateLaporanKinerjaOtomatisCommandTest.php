<?php

use App\Jobs\GenerateLaporanKinerjaJob;
use App\Models\LaporanKinerja;
use App\Models\Triwulan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;

afterEach(function () {
    Carbon::setTestNow();
});

it('tidak melakukan apa pun jika bukan tanggal 1', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-15'));
    Queue::fake([GenerateLaporanKinerjaJob::class]);

    $this->artisan('laporan-kinerja:generate-otomatis')->assertSuccessful();

    Queue::assertNothingPushed();
});

it('selalu generate laporan bulanan untuk bulan lalu pada tanggal 1', function () {
    Carbon::setTestNow(Carbon::parse('2026-05-01'));
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    makeTahunAnggaran(2026);

    $this->artisan('laporan-kinerja:generate-otomatis')->assertSuccessful();

    $this->assertDatabaseHas('laporan_kinerja', ['jenis' => 'bulanan', 'bulan' => 4]);
});

it('generate laporan triwulanan TW1 saat tanggal 1 April', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-01'));
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $tahun = makeTahunAnggaran(2026);
    $tw1 = Triwulan::where('kode', 'TW1')->value('id');

    $this->artisan('laporan-kinerja:generate-otomatis')->assertSuccessful();

    $this->assertDatabaseHas('laporan_kinerja', [
        'jenis' => 'triwulanan',
        'triwulan_id' => $tw1,
        'tahun_anggaran_id' => $tahun->id,
    ]);
});

it('generate laporan TW4 dan tahunan untuk tahun sebelumnya saat tanggal 1 Januari', function () {
    Carbon::setTestNow(Carbon::parse('2027-01-01'));
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $tahunLalu = makeTahunAnggaran(2026);
    $tw4 = Triwulan::where('kode', 'TW4')->value('id');

    $this->artisan('laporan-kinerja:generate-otomatis')->assertSuccessful();

    $this->assertDatabaseHas('laporan_kinerja', [
        'jenis' => 'triwulanan',
        'triwulan_id' => $tw4,
        'tahun_anggaran_id' => $tahunLalu->id,
    ]);
    $this->assertDatabaseHas('laporan_kinerja', [
        'jenis' => 'tahunan',
        'tahun_anggaran_id' => $tahunLalu->id,
    ]);
});

it('tidak error dan tidak membuat laporan apa pun saat Tahun Anggaran terkait belum ada', function () {
    Carbon::setTestNow(Carbon::parse('2026-04-01'));
    Queue::fake([GenerateLaporanKinerjaJob::class]);

    // Sengaja tidak membuat Tahun Anggaran apa pun.
    $this->artisan('laporan-kinerja:generate-otomatis')->assertSuccessful();

    expect(LaporanKinerja::count())->toBe(0);
});