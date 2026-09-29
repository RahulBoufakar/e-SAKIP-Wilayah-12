<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Spek Capaian Kinerja Hybrid §4.1. Header capaian_kinerja dipertahankan
     * sebagai satu-satunya sumber realisasi teragregasi per (IKU, TW, TA):
     * - realisasi_otomatis: hasil hitung dari baris detail yang disetujui.
     * - realisasi_override: true bila Tim Kerja mengganti manual saat kirim.
     * - variabel (JSON formula lama) sudah tidak relevan, diganti 10 tabel
     *   detail per-IKU (§4.3).
     */
    public function up(): void
    {
        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->decimal('realisasi_otomatis', 10, 2)->nullable()->after('realisasi');
            $table->boolean('realisasi_override')->default(false)->after('realisasi_otomatis');
        });

        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->dropColumn('variabel');
        });
    }

    public function down(): void
    {
        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->json('variabel')->nullable()->after('realisasi');
        });

        Schema::table('capaian_kinerja', function (Blueprint $table) {
            $table->dropColumn(['realisasi_otomatis', 'realisasi_override']);
        });
    }
};
