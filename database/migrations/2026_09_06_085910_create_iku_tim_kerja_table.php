<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perubahan aturan: satu IKU/IKK sekarang bisa ditangani lebih dari satu
     * Tim Kerja sekaligus (sebelumnya iku.tim_kerja_id, relasi tunggal).
     * Data lama dipindah ke pivot ini, lalu kolom lama dihapus.
     */
    public function up(): void
    {
        Schema::create('iku_tim_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->cascadeOnDelete();
            $table->foreignId('tim_kerja_id')->constrained('tim_kerja')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['iku_id', 'tim_kerja_id']);
        });

        DB::table('iku')
            ->whereNotNull('tim_kerja_id')
            ->get(['id', 'tim_kerja_id'])
            ->each(function ($row) {
                DB::table('iku_tim_kerja')->insert([
                    'iku_id' => $row->id,
                    'tim_kerja_id' => $row->tim_kerja_id,
                    'created_at' => now(),
                ]);
            });

        Schema::table('iku', function (Blueprint $table) {
            $table->dropForeign(['tim_kerja_id']);
            $table->dropColumn('tim_kerja_id');
        });
    }

    public function down(): void
    {
        Schema::table('iku', function (Blueprint $table) {
            $table->foreignId('tim_kerja_id')->nullable()->after('formula_kode')->constrained('tim_kerja')->nullOnDelete();
        });

        // Catatan: kalau satu IKU sempat punya >1 Tim Kerja, rollback ini
        // hanya menyisakan salah satu (kembali ke relasi tunggal).
        DB::table('iku_tim_kerja')
            ->get(['iku_id', 'tim_kerja_id'])
            ->each(function ($row) {
                DB::table('iku')->where('id', $row->iku_id)->update(['tim_kerja_id' => $row->tim_kerja_id]);
            });

        Schema::dropIfExists('iku_tim_kerja');
    }
};