<?php

use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\LaporanKinerja;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use App\Models\TimKerja;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\User;
use App\Models\UsulanProgramKerja;
use Database\Seeders\RoleSeeder;
use Database\Seeders\TriwulanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)
    ->beforeEach(function () {
        config(['cache.default' => 'array']); // hindari Cache::forget()/remember() menyentuh driver DB asli saat test
        (new RoleSeeder())->run();
        (new TriwulanSeeder())->run();
    })
    ->in('Feature');

function userWithRole(string $role, array $attrs = []): User
{
    $user = User::factory()->create($attrs);
    $user->assignRole($role);

    return $user;
}

function makeTahunAnggaran(int $tahun = 2026): TahunAnggaran
{
    return TahunAnggaran::create(['tahun' => $tahun]);
}

function makeSasaranKegiatan(TahunAnggaran $tahunAnggaran, string $nama = 'Sasaran Uji'): SasaranKegiatan
{
    return SasaranKegiatan::create([
        'tahun_anggaran_id' => $tahunAnggaran->id,
        'nama_sasaran' => $nama,
    ]);
}

function makeIku(SasaranKegiatan $sasaran, array $attrs = []): Iku
{
    return Iku::create(array_merge([
        'sasaran_kegiatan_id' => $sasaran->id,
        'jenis' => 'IKU',
        'deskripsi' => 'Deskripsi IKU Uji',
        'target_pk' => 100,
        'satuan' => '%',
    ], $attrs));
}

function makeTimKerja(string $nama = 'Tim Uji'): TimKerja
{
    return TimKerja::firstOrCreate(['nama_tim' => $nama]);
}

function activateTriwulan(TahunAnggaran $tahunAnggaran, string $kode = 'TW1'): void
{
    $triwulanId = Triwulan::where('kode', $kode)->value('id');
    TriwulanStatus::activate($triwulanId, $tahunAnggaran->id);
}

function makeUsulan(Iku $iku, array $attrs = []): UsulanProgramKerja
{
    return UsulanProgramKerja::create(array_merge([
        'iku_id' => $iku->id,
        'nama_usulan' => 'Usulan Uji',
        'tahun' => 2026,
        'status_validasi' => 'draft', // eksplisit — jangan andalkan default kolom DB
    ], $attrs));
}

function makeCapaianKinerja(Iku $iku, TahunAnggaran $tahunAnggaran, array $attrs = []): CapaianKinerja
{
    $triwulanId = $attrs['triwulan_id'] ?? Triwulan::where('kode', 'TW1')->value('id');

    return CapaianKinerja::create(array_merge([
        'iku_id' => $iku->id,
        'triwulan_id' => $triwulanId,
        'tahun_anggaran_id' => $tahunAnggaran->id,
        'status' => 'draft', // eksplisit
    ], $attrs));
}

function makeAnalisaKinerja(Iku $iku, TahunAnggaran $tahunAnggaran, array $attrs = []): AnalisaKinerja
{
    $triwulanId = $attrs['triwulan_id'] ?? Triwulan::where('kode', 'TW1')->value('id');

    return AnalisaKinerja::create(array_merge([
        'iku_id' => $iku->id,
        'triwulan_id' => $triwulanId,
        'tahun_anggaran_id' => $tahunAnggaran->id,
        'status' => 'menunggu_validasi',
    ], $attrs));
}

function makeLaporanKinerja(TahunAnggaran $tahunAnggaran, array $attrs = []): LaporanKinerja
{
    return LaporanKinerja::create(array_merge([
        'jenis' => 'tahunan',
        'tahun_anggaran_id' => $tahunAnggaran->id,
        'bulan' => null,
        'triwulan_id' => null,
        'versi' => 1,
        'status' => 'diproses',
    ], $attrs));
}