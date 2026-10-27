<?php

use App\Jobs\SinkronisasiPtsJob;
use App\Models\SinkronisasiPts;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    config([
        'pddikti.driver' => 'lldikti12',
        'pddikti.drivers.lldikti12.base_url' => 'https://api.test/v1',
        'pddikti.drivers.lldikti12.api_key' => 'kunci-uji',
    ]);
    $this->admin = userWithRole('admin');
});

function buatLogSinkron(string $jenis = 'pts', string $status = 'diproses', int $menitLalu = 0): SinkronisasiPts
{
    $log = SinkronisasiPts::create(['jenis' => $jenis, 'status' => $status]);
    if ($menitLalu) {
        $log->forceFill(['created_at' => now()->subMinutes($menitLalu)])->save();
    }

    return $log;
}

it('hanya admin yang bisa membuka dan menjalankan sinkronisasi', function () {
    $timKerja = userWithRole('tim_kerja');

    $this->actingAs($timKerja)->get(route('admin.tools.sinkronisasi.index'))->assertForbidden();
    $this->actingAs($timKerja)->post(route('admin.tools.sinkronisasi.pts'))->assertForbidden();
    expect(SinkronisasiPts::count())->toBe(0);
});

it('halaman menampilkan tombol, riwayat, dan atribusi sumber', function () {
    buatLogSinkron('pts', 'berhasil');

    $this->actingAs($this->admin)->get(route('admin.tools.sinkronisasi.index'))
        ->assertOk()
        ->assertSee('Sinkronkan Semua')
        ->assertSee('Riwayat Sinkronisasi')
        ->assertSee('Sumber: LLDIKTI Wilayah XII');
});

it('POST pts membuat log diproses dan mengantrekan satu job tanpa lanjutan', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.pts'))
        ->assertRedirect()
        ->assertSessionHas('feedback.type', 'success');

    $log = SinkronisasiPts::first();
    expect($log->jenis)->toBe('pts')
        ->and($log->status)->toBe('diproses')
        ->and($log->dipicu_oleh)->toEqual($this->admin->id);
    Queue::assertPushed(SinkronisasiPtsJob::class, 1);
    Queue::assertPushed(SinkronisasiPtsJob::class, fn ($j) => $j->log->is($log) && $j->lanjutan === null);
});

it('POST akreditasi membuat log jenis akreditasi', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.akreditasi'))->assertRedirect();

    expect(SinkronisasiPts::first()->jenis)->toBe('akreditasi');
    Queue::assertPushed(SinkronisasiPtsJob::class, 1);
});

it('POST semua membuat dua log dan satu job PTS dengan lanjutan akreditasi', function () {
    Queue::fake();

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.semua'))->assertRedirect();

    expect(SinkronisasiPts::count())->toBe(2);
    Queue::assertPushed(SinkronisasiPtsJob::class, 1);
    Queue::assertPushed(SinkronisasiPtsJob::class, fn ($j) => $j->log->jenis === 'pts' && $j->lanjutan?->jenis === 'akreditasi');
});

it('menolak sinkronisasi baru selama masih ada log diproses', function () {
    Queue::fake();
    buatLogSinkron('pts', 'diproses');

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.semua'))
        ->assertSessionHas('feedback.type', 'error');

    expect(SinkronisasiPts::count())->toBe(1);
    Queue::assertNothingPushed();
});

it('menolak bila API key kosong, tanpa membuat log atau job', function () {
    Queue::fake();
    config(['pddikti.drivers.lldikti12.api_key' => '']);

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.pts'))
        ->assertSessionHas('feedback.type', 'error');

    expect(SinkronisasiPts::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('galat saat mengantrekan menandai log gagal supaya tidak menggantung diproses', function () {
    config(['pddikti.queue.koneksi' => 'tidak-ada']); // koneksi tak terdaftar -> dispatch melempar galat

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.semua'))
        ->assertSessionHas('feedback.type', 'error');

    expect(SinkronisasiPts::pluck('status')->unique()->all())->toBe(['gagal']);
});

it('status mengembalikan peta id ke status', function () {
    $a = buatLogSinkron('pts', 'diproses');
    $b = buatLogSinkron('akreditasi', 'berhasil');

    $res = $this->actingAs($this->admin)
        ->getJson(route('admin.tools.sinkronisasi.status', ['ids' => [$a->id, $b->id]]))
        ->assertOk();

    expect($res->json((string) $a->id))->toBe('diproses')
        ->and($res->json((string) $b->id))->toBe('berhasil');
});

it('tandai gagal hanya untuk log yang macet lebih dari 15 menit', function () {
    $baru = buatLogSinkron('akreditasi', 'diproses', 2);

    $this->actingAs($this->admin)->put(route('admin.tools.sinkronisasi.tandai-gagal', $baru))
        ->assertSessionHas('feedback.type', 'error');

    expect($baru->fresh()->status)->toBe('diproses');
});

it('log macet bisa ditandai gagal, lalu sinkronisasi baru dapat dijalankan', function () {
    Queue::fake();
    $macet = buatLogSinkron('pts', 'diproses', 20);

    $this->actingAs($this->admin)->get(route('admin.tools.sinkronisasi.index'))
        ->assertOk()->assertSee('Tandai Gagal');

    $this->actingAs($this->admin)->put(route('admin.tools.sinkronisasi.tandai-gagal', $macet))
        ->assertSessionHas('feedback.type', 'success');
    expect($macet->fresh()->status)->toBe('gagal')->and($macet->fresh()->selesai_at)->not->toBeNull();

    $this->actingAs($this->admin)->post(route('admin.tools.sinkronisasi.pts'))
        ->assertSessionHas('feedback.type', 'success');
    Queue::assertPushed(SinkronisasiPtsJob::class, 1);
});

it('log yang sudah selesai tidak bisa ditandai gagal', function () {
    $selesai = buatLogSinkron('pts', 'berhasil', 30);

    $this->actingAs($this->admin)->put(route('admin.tools.sinkronisasi.tandai-gagal', $selesai))
        ->assertSessionHas('feedback.type', 'error');

    expect($selesai->fresh()->status)->toBe('berhasil');
});