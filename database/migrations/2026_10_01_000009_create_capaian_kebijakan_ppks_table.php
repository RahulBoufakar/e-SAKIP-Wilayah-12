<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 6 — IKU 5. Dedup per PTS mensyaratkan 3 kolom terisi (§5 Pola 4).
    public function up(): void
    {
        Schema::create('capaian_kebijakan_ppks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->text('bentuk_implementasi_ppks')->nullable();
            $table->text('bentuk_implementasi_anti_narkoba')->nullable();
            $table->text('bentuk_implementasi_anti_korupsi')->nullable();
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_kebijakan_ppks');
    }
};
