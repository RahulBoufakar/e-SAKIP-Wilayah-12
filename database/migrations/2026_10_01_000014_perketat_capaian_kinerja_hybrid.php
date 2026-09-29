<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ENTRI_TUNGGAL = [
        'capaian_kepuasan_layanan' => 'ck_kepuasan_tunggal_uq',
        'capaian_tata_kelola'      => 'ck_tatakelola_tunggal_uq',
        'capaian_nilai_rka'        => 'ck_nilairka_tunggal_uq',
    ];

    public function up(): void
    {
        // IKU 2.2: teks -> file PDF
        Schema::table('capaian_kebijakan_ppks', function (Blueprint $t) {
            $t->string('file_implementasi_ppks')->nullable()->after('pts_id');
            $t->string('file_implementasi_anti_narkoba')->nullable()->after('file_implementasi_ppks');
            $t->string('file_implementasi_anti_korupsi')->nullable()->after('file_implementasi_anti_narkoba');
        });
        Schema::table('capaian_kebijakan_ppks', function (Blueprint $t) {
            $t->dropColumn(['bentuk_implementasi_ppks', 'bentuk_implementasi_anti_narkoba', 'bentuk_implementasi_anti_korupsi']);
        });

        // IKU terkait dosen: IKU 3.3 butuh NIDN (IKU 3.2 sudah punya kolom nidn)
        Schema::table('capaian_fasilitasi_penelitian', function (Blueprint $t) {
            $t->string('nidn', 20)->nullable()->after('pts_id');
        });

        // IKU 1.1, 1.3, 4.1: satu baris per header (= per IKU + triwulan + tahun)
        foreach (self::ENTRI_TUNGGAL as $tabel => $nama) {
            Schema::table($tabel, fn (Blueprint $t) => $t->unique('capaian_kinerja_id', $nama));
        }

        // Anti-duplikasi (semua di-scope per header capaian_kinerja_id)
        Schema::table('capaian_akreditasi_pts', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id'], 'ck_akreditasi_pts_uq'));
        Schema::table('capaian_penggabungan_pts', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id'], 'ck_penggabungan_pts_uq'));
        Schema::table('capaian_kebijakan_ppks', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id'], 'ck_ppks_pts_uq'));
        Schema::table('capaian_dosen_naik_jafung', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'nidn'], 'ck_jafung_nidn_uq'));
        Schema::table('capaian_fasilitasi_mutu_pts', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', 'tanggal_kegiatan'], 'ck_mutu_dup_uq'));
        Schema::table('capaian_fasilitasi_kemahasiswaan', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id', 'bentuk_fasilitasi', 'tanggal_kegiatan'], 'ck_mhs_dup_uq'));
        Schema::table('capaian_fasilitasi_penelitian', fn (Blueprint $t) => $t->unique(['capaian_kinerja_id', 'pts_id', 'nidn', 'bentuk_fasilitasi'], 'ck_penelitian_dup_uq'));
    }

    public function down(): void
    {
        Schema::table('capaian_fasilitasi_penelitian', fn (Blueprint $t) => $t->dropUnique('ck_penelitian_dup_uq'));
        Schema::table('capaian_fasilitasi_kemahasiswaan', fn (Blueprint $t) => $t->dropUnique('ck_mhs_dup_uq'));
        Schema::table('capaian_fasilitasi_mutu_pts', fn (Blueprint $t) => $t->dropUnique('ck_mutu_dup_uq'));
        Schema::table('capaian_dosen_naik_jafung', fn (Blueprint $t) => $t->dropUnique('ck_jafung_nidn_uq'));
        Schema::table('capaian_kebijakan_ppks', fn (Blueprint $t) => $t->dropUnique('ck_ppks_pts_uq'));
        Schema::table('capaian_penggabungan_pts', fn (Blueprint $t) => $t->dropUnique('ck_penggabungan_pts_uq'));
        Schema::table('capaian_akreditasi_pts', fn (Blueprint $t) => $t->dropUnique('ck_akreditasi_pts_uq'));
        foreach (self::ENTRI_TUNGGAL as $tabel => $nama) {
            Schema::table($tabel, fn (Blueprint $t) => $t->dropUnique($nama));
        }
        Schema::table('capaian_fasilitasi_penelitian', fn (Blueprint $t) => $t->dropColumn('nidn'));

        Schema::table('capaian_kebijakan_ppks', function (Blueprint $t) {
            $t->text('bentuk_implementasi_ppks')->nullable();
            $t->text('bentuk_implementasi_anti_narkoba')->nullable();
            $t->text('bentuk_implementasi_anti_korupsi')->nullable();
        });
        Schema::table('capaian_kebijakan_ppks', function (Blueprint $t) {
            $t->dropColumn(['file_implementasi_ppks', 'file_implementasi_anti_narkoba', 'file_implementasi_anti_korupsi']);
        });
    }
};