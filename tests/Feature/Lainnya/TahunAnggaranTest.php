<?php

use App\Models\TahunAnggaran;
use App\Models\TriwulanStatus;

it('auto-seed baris triwulan_status non_aktif untuk semua triwulan saat tahun anggaran dibuat (Rule D-1)', function () {
    $tahun = makeTahunAnggaran();

    $rows = TriwulanStatus::where('tahun_anggaran_id', $tahun->id)->get();

    expect($rows)->toHaveCount(4)
        ->and($rows->pluck('status')->unique()->all())->toBe(['non_aktif']);
});

it('tidak melacak updated_at karena hanya mendukung create/delete (FR-25)', function () {
    expect(TahunAnggaran::UPDATED_AT)->toBeNull();
});

it('memblokir hapus tahun anggaran yang masih punya sasaran kegiatan (FK RESTRICT)', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();
    makeSasaranKegiatan($tahun);

    $response = $this->actingAs($admin)->delete(route('admin.tools.tahun.destroy', $tahun->id));

    $response->assertRedirect();
    $this->assertDatabaseHas('tahun_anggaran', ['id' => $tahun->id]);
    expect(session('feedback')['type'])->toBe('error');
});

it('menghapus tahun anggaran yang tidak punya data anak', function () {
    $admin = userWithRole('admin');
    $tahun = makeTahunAnggaran();

    $this->actingAs($admin)->delete(route('admin.tools.tahun.destroy', $tahun->id));

    $this->assertDatabaseMissing('tahun_anggaran', ['id' => $tahun->id]);
});