<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABEL = [
        'capaian_akreditasi_pts',
        'capaian_penggabungan_pts',
        'capaian_kebijakan_ppks',
        'capaian_fasilitasi_mutu_pts',
        'capaian_fasilitasi_kemahasiswaan',
        'capaian_dosen_naik_jafung',
        'capaian_fasilitasi_penelitian',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) use ($tabel) {
                // Id baris asal (triwulan sebelumnya). Null = baris mandiri / tautan diputus.
                $table->unsignedBigInteger('sumber_baris_id')->nullable()->after('capaian_kinerja_id');
                $table->foreign('sumber_baris_id')->references('id')->on($tabel)->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        foreach (self::TABEL as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                // SQLite tidak mendukung drop foreign key secara langsung (pola sama dengan migration iku_tim_kerja)
                if (DB::getDriverName() !== 'sqlite') {
                    $table->dropForeign(['sumber_baris_id']);
                }
                $table->dropColumn('sumber_baris_id');
            });
        }
    }
};
