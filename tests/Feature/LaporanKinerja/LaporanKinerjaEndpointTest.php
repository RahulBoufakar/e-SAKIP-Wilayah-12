<?php

use App\Jobs\GenerateLaporanKinerjaJob;
use App\Models\LaporanKinerja;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->tahun = makeTahunAnggaran();
});

it('pimpinan dapat membuat request generate laporan bulanan dan job di-dispatch', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $pimpinan = userWithRole('pimpinan');

    $response = $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'bulanan',
        'bulan' => 6,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('laporan_kinerja', [
        'jenis' => 'bulanan',
        'tahun_anggaran_id' => $this->tahun->id,
        'bulan' => 6,
        'status' => 'diproses',
        'versi' => 1,
        'generated_by' => $pimpinan->id,
    ]);

    Queue::assertPushed(GenerateLaporanKinerjaJob::class);
});

it('menaikkan versi saat generate ulang untuk periode yang sama', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $pimpinan = userWithRole('pimpinan');

    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
    ]);
    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
    ]);

    expect(LaporanKinerja::where('jenis', 'tahunan')->orderBy('versi')->pluck('versi')->all())->toBe([1, 2]);
});

it('mewajibkan bulan untuk laporan bulanan dan triwulan untuk laporan triwulanan', function () {
    $pimpinan = userWithRole('pimpinan');

    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'bulanan',
    ])->assertSessionHasErrors('bulan');

    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'triwulanan',
    ])->assertSessionHasErrors('triwulan_id');
});

it('generate manual tercatat di audit log tanpa mengirim notifikasi bell', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $pimpinan = userWithRole('pimpinan');

    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
    ]);

    $activity = Activity::where('log_name', 'audit_trail')->latest()->first();

    expect($activity)->not->toBeNull()
        ->and($activity->causer_id)->toBe($pimpinan->id)
        ->and($activity->description)->toContain('generate manual');

    $this->assertDatabaseCount('notifications', 0);
});

it('admin dapat generate manual lewat jalur cadangan Tools', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $admin = userWithRole('admin');

    $response = $this->actingAs($admin)->post(route('admin.tools.laporan-pimpinan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('laporan_kinerja', [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
        'generated_by' => $admin->id,
    ]);
});

it('hanya bisa mengunduh laporan yang berstatus berhasil dan filenya benar-benar ada', function () {
    Storage::fake('laporan');
    $pimpinan = userWithRole('pimpinan');

    $diproses = makeLaporanKinerja($this->tahun, ['status' => 'diproses']);
    $gagal = makeLaporanKinerja($this->tahun, ['status' => 'gagal', 'catatan' => 'Data tidak lengkap']);
    $berhasilTanpaFile = makeLaporanKinerja($this->tahun, ['status' => 'berhasil', 'file_path' => 'tahunan/tidak-ada.pdf']);

    Storage::disk('laporan')->put('tahunan/ada.pdf', 'dummy-pdf-content');
    $berhasil = makeLaporanKinerja($this->tahun, ['status' => 'berhasil', 'file_path' => 'tahunan/ada.pdf']);

    $this->actingAs($pimpinan)->get(route('pimpinan.laporan.unduh', $diproses))->assertNotFound();
    $this->actingAs($pimpinan)->get(route('pimpinan.laporan.unduh', $gagal))->assertNotFound();
    $this->actingAs($pimpinan)->get(route('pimpinan.laporan.unduh', $berhasilTanpaFile))->assertNotFound();
    $this->actingAs($pimpinan)->get(route('pimpinan.laporan.unduh', $berhasil))->assertOk();
});

it('endpoint status hanya mengembalikan status untuk id yang diminta', function () {
    $pimpinan = userWithRole('pimpinan');

    $satu = makeLaporanKinerja($this->tahun, ['status' => 'diproses']);
    $dua = makeLaporanKinerja($this->tahun, ['status' => 'berhasil', 'versi' => 2]);
    makeLaporanKinerja($this->tahun, ['status' => 'gagal', 'versi' => 3]);

    $response = $this->actingAs($pimpinan)->getJson(route('pimpinan.laporan.status', ['ids' => [$satu->id, $dua->id]]));

    $response->assertOk();
    expect($response->json())->toEqual([
        (string) $satu->id => 'diproses',
        (string) $dua->id => 'berhasil',
    ]);
});

it('memfilter daftar laporan berdasarkan jenis dan tahun anggaran', function () {
    $pimpinan = userWithRole('pimpinan');
    $tahunLain = makeTahunAnggaran($this->tahun->tahun + 1);

    makeLaporanKinerja($this->tahun, ['jenis' => 'bulanan', 'bulan' => 1]);
    makeLaporanKinerja($this->tahun, ['jenis' => 'tahunan']);
    makeLaporanKinerja($tahunLain, ['jenis' => 'tahunan']);

    $response = $this->actingAs($pimpinan)->get(route('pimpinan.laporan.index', [
        'jenis' => 'tahunan',
        'tahun_anggaran_id' => $this->tahun->id,
    ]));

    $response->assertOk();
    expect($response->viewData('laporanList')->total())->toBe(1);
});

// --- AUDIT § A8: Cache::lock() untuk idempotency generate laporan ---

it('melepas Cache::lock setelah proses generate selesai, sehingga bisa diambil lagi oleh proses berikutnya', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);
    $pimpinan = userWithRole('pimpinan');

    $this->actingAs($pimpinan)->post(route('pimpinan.laporan.generate'), [
        'tahun_anggaran_id' => $this->tahun->id,
        'jenis' => 'tahunan',
    ])->assertRedirect();

    $lockKey = "generate-laporan:tahunan:{$this->tahun->id}::";
    $lock = Cache::lock($lockKey, 10);

    // Kalau ini gagal (false), berarti lock lama masih dipegang -> tidak
    // pernah dilepas -> generate laporan berikutnya untuk periode yang sama
    // akan macet permanen menunggu lock yang tidak akan pernah lepas sendiri.
    expect($lock->get())->toBeTrue();
    $lock->release();
});

it('melempar LockTimeoutException saat lock masih dipegang proses lain untuk kombinasi periode yang sama (cegah generate ganda)', function () {
    $lockKey = "generate-laporan:tahunan:{$this->tahun->id}::";
    $externalLock = Cache::lock($lockKey, 10);
    expect($externalLock->get())->toBeTrue(); // simulasikan proses lain sedang memegang lock

    // Test ini sengaja butuh ~5 detik nyata (block(5) di LaporanKinerjaService)
    // untuk benar-benar memverifikasi lock diblokir, bukan diam-diam diloloskan.
    app(\App\Services\LaporanKinerjaService::class)->generateTahunan($this->tahun->id);

    $externalLock->release();
})->throws(LockTimeoutException::class);

it('tidak membuat baris laporan baru apa pun selama lock masih dipegang pihak lain', function () {
    $lockKey = "generate-laporan:tahunan:{$this->tahun->id}::";
    $externalLock = Cache::lock($lockKey, 10);
    $externalLock->get();

    try {
        app(\App\Services\LaporanKinerjaService::class)->generateTahunan($this->tahun->id);
    } catch (LockTimeoutException $e) {
        // diharapkan — lihat test sebelumnya
    } finally {
        $externalLock->release();
    }

    expect(LaporanKinerja::where('jenis', 'tahunan')->where('tahun_anggaran_id', $this->tahun->id)->count())->toBe(0);
});

it('lock per kombinasi periode tidak saling memblokir periode yang berbeda', function () {
    Queue::fake([GenerateLaporanKinerjaJob::class]);

    // Pegang lock untuk laporan TAHUNAN...
    $lockTahunan = Cache::lock("generate-laporan:tahunan:{$this->tahun->id}::", 10);
    $lockTahunan->get();

    // ...tapi generate laporan BULANAN untuk tahun yang sama tetap harus lolos
    // tanpa menunggu, karena lock key-nya berbeda (bukan lock global).
    $laporanBulanan = app(\App\Services\LaporanKinerjaService::class)->generateBulanan($this->tahun->id, 5);

    expect($laporanBulanan)->not->toBeNull()
        ->and($laporanBulanan->status)->toBe('diproses');

    $lockTahunan->release();
});
