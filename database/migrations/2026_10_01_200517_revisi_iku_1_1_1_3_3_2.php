<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // // IKU 1.1: responden_puas (hitung manual) -> hasil_perhitungan_kepuasan (input langsung, %)
        // Schema::table('capaian_kepuasan_layanan', function (Blueprint $table) {
        //     $table->decimal('hasil_perhitungan_kepuasan', 5, 2)->nullable()->after('total_responden');
        // });
        // Schema::table('capaian_kepuasan_layanan', function (Blueprint $table) {
        //     $table->dropColumn('responden_puas');
        // });

        // // IKU 1.3: hapus no_sk (tidak dipakai lagi)
        // Schema::table('capaian_tata_kelola', function (Blueprint $table) {
        //     $table->dropColumn('no_sk');
        // });

        // IKU 3.2: nidn -> nidn/nuptk maks 16 karakter, + tipe_kepegawaian
        Schema::table('capaian_dosen_naik_jafung', function (Blueprint $table) {
            $table->string('nidn', 16)->change();
            $table->enum('tipe_kepegawaian', ['Dosen Tetap Yayasan', 'Dosen PNS'])->nullable()->after('nidn');
        });
    }

    public function down(): void
    {
        Schema::table('capaian_dosen_naik_jafung', function (Blueprint $table) {
            $table->dropColumn('tipe_kepegawaian');
            $table->string('nidn', 20)->change();
        });

        Schema::table('capaian_tata_kelola', function (Blueprint $table) {
            $table->string('no_sk')->nullable();
        });

        Schema::table('capaian_kepuasan_layanan', function (Blueprint $table) {
            $table->unsignedInteger('responden_puas')->default(0);
        });
        Schema::table('capaian_kepuasan_layanan', function (Blueprint $table) {
            $table->dropColumn('hasil_perhitungan_kepuasan');
        });
    }
};