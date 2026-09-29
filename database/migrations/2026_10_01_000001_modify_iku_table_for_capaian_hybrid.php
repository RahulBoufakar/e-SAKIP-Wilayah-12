<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spek Capaian Kinerja Hybrid §4.1: formula_kode (arsitektur registry
     * generik, ditolak — lihat §2) digantikan tipe_iku, penanda "IKU resmi
     * yang mana" dipakai untuk routing ke controller/Blade yang benar.
     * Nullable supaya IKU lama/di luar scope 9 tipe hybrid tetap valid.
     */
    public function up(): void
    {
        Schema::table('iku', function (Blueprint $table) {
            $table->string('tipe_iku', 50)->nullable()->after('formula_kode');
        });

        Schema::table('iku', function (Blueprint $table) {
            $table->dropColumn('formula_kode');
        });
    }

    public function down(): void
    {
        Schema::table('iku', function (Blueprint $table) {
            $table->string('formula_kode')->nullable()->after('deskripsi');
        });

        Schema::table('iku', function (Blueprint $table) {
            $table->dropColumn('tipe_iku');
        });
    }
};
