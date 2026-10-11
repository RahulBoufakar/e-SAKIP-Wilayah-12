<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

/** Buat berkas di disk private lalu atur umurnya (menit yang lalu). */
function tempRab(string $path, int $umurMenit): void
{
    Storage::disk('private')->put($path, 'x');
    touch(Storage::disk('private')->path($path), time() - $umurMenit * 60);
}

beforeEach(function () {
    Storage::fake('private');
});

it('S6: berkas temp lebih dari 1 jam dihapus, yang lebih baru tidak', function () {
    tempRab('temp/lama.xlsx', 61);
    tempRab('temp/baru.xlsx', 59);
    tempRab('temp/sangat-baru.xlsx', 1);

    $this->artisan('rab-generator:cleanup-temp')->expectsOutput('Selesai. Berkas temp dihapus: 1.')->assertSuccessful();

    Storage::disk('private')->assertMissing('temp/lama.xlsx');
    Storage::disk('private')->assertExists('temp/baru.xlsx');
    Storage::disk('private')->assertExists('temp/sangat-baru.xlsx');
});

it('hanya menyentuh temp/*.xlsx: berkas lain dan folder lain dibiarkan walau tua', function () {
    tempRab('temp/catatan.txt', 600);
    tempRab('rab-master/master.xlsx', 600);
    tempRab('temp/lama.xlsx', 600);

    $this->artisan('rab-generator:cleanup-temp')->assertSuccessful();

    Storage::disk('private')->assertExists('temp/catatan.txt');
    Storage::disk('private')->assertExists('rab-master/master.xlsx');
    Storage::disk('private')->assertMissing('temp/lama.xlsx');
});

it('batas dapat diubah lewat --menit', function () {
    tempRab('temp/sepuluh-menit.xlsx', 10);

    $this->artisan('rab-generator:cleanup-temp', ['--menit' => 5])->assertSuccessful();

    Storage::disk('private')->assertMissing('temp/sepuluh-menit.xlsx');
});

it('aman dijalankan bila folder temp belum ada', function () {
    $this->artisan('rab-generator:cleanup-temp')->expectsOutput('Selesai. Berkas temp dihapus: 0.')->assertSuccessful();
});

it('terdaftar di scheduler tiap 15 menit', function () {
    Artisan::call('list'); // bootstrap kernel konsol supaya Kernel::schedule() dijalankan

    $event = collect(app(Schedule::class)->events())
        ->first(fn ($e) => str_contains($e->command, 'rab-generator:cleanup-temp'));

    expect($event)->not->toBeNull()
        ->and($event->expression)->toBe('*/15 * * * *');
});
