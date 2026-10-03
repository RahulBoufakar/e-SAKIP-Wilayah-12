<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * IKU 3.3: pembagi (jumlah publikasi) kini per header capaian (per triwulan),
     * bukan per tahun anggaran. Baris lama (capaian_kinerja_id NULL) diabaikan service.
     */
    public function up(): void
    {
        Schema::table('jumlah_publikasi', function (Blueprint $table) {
            // tahun_anggaran_id dipertahankan; kini REDUNDAN (bisa diturunkan dari header capaian_kinerja).
            $table->foreignId('capaian_kinerja_id')->nullable()->unique()->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('diperbarui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        // FK & unique dulu, baru kolom. SQLite tidak mendukung drop foreign key langsung.
        Schema::table('jumlah_publikasi', function (Blueprint $table) {
            if (DB::getDriverName() !== 'sqlite') {
                $table->dropForeign(['capaian_kinerja_id']);
                $table->dropForeign(['diperbarui_oleh']);
            }
            $table->dropUnique(['capaian_kinerja_id']);
        });

        Schema::table('jumlah_publikasi', function (Blueprint $table) {
            $table->dropColumn(['capaian_kinerja_id', 'diperbarui_oleh', 'updated_at']);
        });
    }
};