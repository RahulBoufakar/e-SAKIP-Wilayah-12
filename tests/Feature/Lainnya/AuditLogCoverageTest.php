<?php

use App\Events\ActivityOccurred;
use App\Models\JumlahMahasiswa;
use App\Models\JumlahPts;
use App\Models\Pts;
use Database\Seeders\TemplateDokumenSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Event::fake([ActivityOccurred::class]);
});

it('mencatat audit log saat admin menghapus user, dengan snapshot data sebelum dihapus', function () {
    $admin = userWithRole('admin');
    $timKerjaUser = userWithRole('tim_kerja', ['email' => 'dihapus@test.local', 'name' => 'User Dihapus']);

    $this->actingAs($admin)->delete(route('admin.master-data.user.destroy', $timKerjaUser->id));

    Event::assertDispatched(ActivityOccurred::class, function ($event) {
        return str_contains($event->description, 'menghapus user')
            && str_contains($event->description, 'User Dihapus')
            && ($event->properties['email'] ?? null) === 'dihapus@test.local';
    });
});

it('mencatat audit log saat email user diubah walau role/tim tidak berubah', function () {
    $admin = userWithRole('admin');
    $timKerjaUser = userWithRole('tim_kerja', ['email' => 'lama@test.local']);
    $tim = makeTimKerja();
    $timKerjaUser->timKerja()->attach($tim->id);

    $this->actingAs($admin)->put(route('admin.master-data.user.update', $timKerjaUser->id), [
        'name' => $timKerjaUser->name,
        'email' => 'baru@test.local',
        'password' => '',
        'role' => 'tim_kerja',
        'tim_kerja_id' => $tim->id,
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'email: lama@test.local'));
});

it('mencatat audit log saat password user direset meski email/role/tim tidak berubah', function () {
    $admin = userWithRole('admin');
    $timKerjaUser = userWithRole('tim_kerja', ['email' => 'tetap@test.local']);
    $tim = makeTimKerja();
    $timKerjaUser->timKerja()->attach($tim->id);

    $this->actingAs($admin)->put(route('admin.master-data.user.update', $timKerjaUser->id), [
        'name' => $timKerjaUser->name,
        'email' => 'tetap@test.local',
        'password' => 'password-baru-123',
        'role' => 'tim_kerja',
        'tim_kerja_id' => $tim->id,
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'password direset'));
});

it('tidak mencatat audit log saat update user tanpa perubahan apa pun', function () {
    $admin = userWithRole('admin');
    $timKerjaUser = userWithRole('tim_kerja', ['email' => 'sama@test.local']);
    $tim = makeTimKerja();
    $timKerjaUser->timKerja()->attach($tim->id);

    $this->actingAs($admin)->put(route('admin.master-data.user.update', $timKerjaUser->id), [
        'name' => $timKerjaUser->name,
        'email' => 'sama@test.local',
        'password' => '',
        'role' => 'tim_kerja',
        'tim_kerja_id' => $tim->id,
    ]);

    Event::assertNotDispatched(ActivityOccurred::class);
});

it('mencatat audit log saat Jumlah Mahasiswa ditambah dan dihapus', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $this->actingAs($admin)->post(route('admin.tools.jumlah-mahasiswa.store'), [
        'tahun_anggaran_id' => $tahun->id,
        'jumlah' => 500,
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menambahkan data Jumlah Mahasiswa'));

    $record = JumlahMahasiswa::firstWhere('tahun_anggaran_id', $tahun->id);
    $this->actingAs($admin)->delete(route('admin.tools.jumlah-mahasiswa.destroy', $record->id));

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menghapus data Jumlah Mahasiswa'));
});

it('mencatat audit log saat Jumlah PTS ditambah dan dihapus', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $this->actingAs($admin)->post(route('admin.tools.jumlah-pts.store'), [
        'tahun_anggaran_id' => $tahun->id,
        'jumlah' => 30,
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menambahkan data Jumlah PTS'));

    $record = JumlahPts::firstWhere('tahun_anggaran_id', $tahun->id);
    $this->actingAs($admin)->delete(route('admin.tools.jumlah-pts.destroy', $record->id));

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menghapus data Jumlah PTS'));
});

it('mencatat audit log saat data PTS dibuat, diperbarui, dan dihapus', function () {
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post(route('admin.master-data.pts.store'), [
        'kode_pts' => 'PTS999',
        'nama_pts' => 'Universitas Audit',
        'status_pts' => 'aktif',
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menambahkan data PTS'));

    $pts = Pts::firstWhere('kode_pts', 'PTS999');

    $this->actingAs($admin)->put(route('admin.master-data.pts.update', $pts->id), [
        'kode_pts' => 'PTS999',
        'nama_pts' => 'Universitas Audit Baru',
        'status_pts' => 'aktif',
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'memperbarui data PTS'));

    $this->actingAs($admin)->delete(route('admin.master-data.pts.destroy', $pts->id));

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'menghapus data PTS'));
});

it('mencatat audit log saat Pengaturan Aplikasi diperbarui', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => 'Nama Baru eSAKIP',
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'Pengaturan Aplikasi') && str_contains($e->description, 'nama aplikasi'));
});

it('tidak mencatat audit log saat Pengaturan Aplikasi disubmit tanpa perubahan apa pun', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');

    $this->actingAs($admin)->put(route('admin.pengaturan.aplikasi.update'), [
        'nama_aplikasi' => \App\Models\PengaturanAplikasi::current()->nama_aplikasi,
    ]);

    Event::assertNotDispatched(ActivityOccurred::class);
});

it('mencatat audit log saat file Template Dokumen diperbarui', function () {
    Storage::fake('public');
    $admin = userWithRole('admin');
    (new TemplateDokumenSeeder())->run();

    $this->actingAs($admin)->put(route('admin.pengaturan.template.update', 'rab_pdf'), [
        'file' => UploadedFile::fake()->create('rab.pdf', 100, 'application/pdf'),
    ]);

    Event::assertDispatched(ActivityOccurred::class, fn ($e) => str_contains($e->description, 'template'));
});
