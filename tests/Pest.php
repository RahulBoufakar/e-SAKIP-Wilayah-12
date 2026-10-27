<?php

use App\Models\AnalisaKinerja;
use App\Models\CapaianKinerja;
use App\Models\Iku;
use App\Models\LaporanKinerja;
use App\Models\Pts;
use App\Models\SasaranKegiatan;
use App\Models\TahunAnggaran;
use App\Models\TimKerja;
use App\Models\Triwulan;
use App\Models\TriwulanStatus;
use App\Models\User;
use App\Models\UsulanProgramKerja;
use App\Services\Pddikti\PtsDataSource;
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

function makePts(string $kode = 'P001', array $attrs = []): Pts
{
    return Pts::create(array_merge([
        'kode_pts' => $kode, 'nama_pts' => "PTS {$kode}", 'provinsi' => 'Maluku', 'status_pts' => 'aktif',
        'sumber_data' => 'manual', // eksplisit — jangan andalkan default kolom DB
    ], $attrs));
}

/** Sumber data palsu untuk PtsSyncService: mengembalikan array ternormalisasi apa adanya. */
function sumberPalsu(array $pts = [], array $akreditasi = []): PtsDataSource
{
    return new class($pts, $akreditasi) implements PtsDataSource {
        public function __construct(private array $pts, private array $akreditasi)
        {
        }

        public function ambilSemuaPts(): array
        {
            return $this->pts;
        }

        public function ambilSemuaAkreditasi(): array
        {
            return $this->akreditasi;
        }
    };
}

/** Baris ternormalisasi PTS (bentuk keluaran driver), bentuk nilainya dari dokumentasi Tahap 0. */
function apiPts(array $o = []): array
{
    return $o + [
        'kode_pts' => '121002', 'nama_pts' => 'Universitas Darussalam Ambon', 'provinsi' => 'Maluku', 'status' => 'Aktif',
        'peringkat_akreditasi' => 'Baik Sekali', 'status_akreditasi' => 'Aktif',
    ];
}

function apiAkreditasi(array $o = []): array
{
    return $o + [
        'kode_pts' => '121002', 'peringkat' => 'Baik Sekali', 'no_sk' => 'SK/1',
        'tgl_sk' => '2022-10-11', 'tgl_akhir' => '2027-10-11', 'status' => 'Aktif',
    ];
}