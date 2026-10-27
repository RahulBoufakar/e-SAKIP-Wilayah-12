<?php

use App\Events\ActivityOccurred;
use App\Jobs\SinkronisasiPtsJob;
use App\Models\SinkronisasiPts;
use App\Services\Pddikti\PddiktiApiException;
use App\Services\PtsSyncService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

function logSinkron(string $jenis = 'pts'): SinkronisasiPts
{
    return SinkronisasiPts::create(['jenis' => $jenis, 'dipicu_oleh' => userWithRole('admin')->id]);
}

beforeEach(fn () => config(['pddikti.driver' => 'lldikti12']));

it('memakai koneksi dan antrean dari config', function () {
    $job = new SinkronisasiPtsJob(logSinkron());

    expect($job->connection)->toBe('database')->and($job->queue)->toBe('sinkronisasi');
});

it('handle() menandai log berhasil, menyimpan ringkasan, dan mengirim satu ActivityOccurred', function () {
    Event::fake([ActivityOccurred::class]);
    $log = logSinkron();

    (new SinkronisasiPtsJob($log))->handle(new PtsSyncService(sumberPalsu([apiPts()])));

    $log = $log->fresh();
    expect($log->status)->toBe('berhasil')
        ->and($log->ringkasan['dibuat'])->toBe(1)
        ->and($log->mulai_at)->not->toBeNull()
        ->and($log->selesai_at)->not->toBeNull();
    Event::assertDispatchedTimes(ActivityOccurred::class, 1);
});

it('handle() jenis akreditasi memproses akreditasi', function () {
    Event::fake([ActivityOccurred::class]);
    $pts = makePts('121002');
    $log = logSinkron('akreditasi');

    (new SinkronisasiPtsJob($log))->handle(new PtsSyncService(sumberPalsu([], [apiAkreditasi()])));

    expect($log->fresh()->status)->toBe('berhasil')
        ->and($pts->akreditasi()->exists())->toBeTrue();
});

it('failed() memakai pesan API apa adanya, dan pesan generik untuk galat lain', function () {
    Event::fake([ActivityOccurred::class]);
    $a = logSinkron();
    $b = logSinkron();

    (new SinkronisasiPtsJob($a))->failed(new PddiktiApiException('API key ditolak oleh LLDikti 12 (HTTP 401).'));
    (new SinkronisasiPtsJob($b))->failed(new RuntimeException('SQLSTATE[23000] data rahasia'));

    expect($a->fresh()->status)->toBe('gagal')
        ->and($a->fresh()->catatan)->toContain('API key ditolak')
        ->and($b->fresh()->status)->toBe('gagal')
        ->and($b->fresh()->catatan)->not->toContain('SQLSTATE');
    Event::assertDispatchedTimes(ActivityOccurred::class, 2);
});

it('failed() tidak menimpa log yang sudah berhasil', function () {
    Event::fake([ActivityOccurred::class]);
    $log = logSinkron();
    $log->update(['status' => 'berhasil']);

    (new SinkronisasiPtsJob($log))->failed(new RuntimeException('listener error'));

    expect($log->fresh()->status)->toBe('berhasil');
    Event::assertNotDispatched(ActivityOccurred::class);
});

it('handle() mengantrekan job lanjutan setelah log berhasil', function () {
    Event::fake([ActivityOccurred::class]);
    Queue::fake();
    $log = logSinkron('pts');
    $lanjutan = logSinkron('akreditasi');

    (new SinkronisasiPtsJob($log, $lanjutan))->handle(new PtsSyncService(sumberPalsu([apiPts()])));

    expect($log->fresh()->status)->toBe('berhasil');
    Queue::assertPushed(SinkronisasiPtsJob::class, 1);
    Queue::assertPushed(SinkronisasiPtsJob::class, fn ($j) => $j->log->is($lanjutan) && $j->lanjutan === null);
});

it('handle() tidak mengantrekan lanjutan yang sudah ditandai gagal oleh Admin', function () {
    Event::fake([ActivityOccurred::class]);
    Queue::fake();
    $log = logSinkron('pts');
    $lanjutan = logSinkron('akreditasi');
    $lanjutan->update(['status' => 'gagal']);

    (new SinkronisasiPtsJob($log, $lanjutan))->handle(new PtsSyncService(sumberPalsu([apiPts()])));

    Queue::assertNothingPushed();
});

it('failed() membatalkan log lanjutan agar tidak menggantung diproses', function () {
    Event::fake([ActivityOccurred::class]);
    Queue::fake();
    $log = logSinkron('pts');
    $lanjutan = logSinkron('akreditasi');
    $job = new SinkronisasiPtsJob($log, $lanjutan);

    expect(fn () => $job->handle(new PtsSyncService(sumberPalsu([]))))->toThrow(PddiktiApiException::class);
    $job->failed(new PddiktiApiException('Halaman 2 gagal.'));

    expect($log->fresh()->status)->toBe('gagal')
        ->and($lanjutan->fresh()->status)->toBe('gagal')
        ->and($lanjutan->fresh()->catatan)->toContain('PTS gagal');
    Queue::assertNothingPushed();
});