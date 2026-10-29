<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 6 — IKU 2.2. Satu dokumen implementasi PPKS/anti-narkoba/anti-korupsi
    // (file_implementasi_...) + file_bukti_dukung opsional.
    public function up(): void
    {
        Schema::create('capaian_kebijakan_ppks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            // Id baris asal (triwulan sebelumnya). Null = baris mandiri / tautan diputus.
            $table->unsignedBigInteger('sumber_baris_id')->nullable();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->string('file_implementasi_ppks_antinarkoba_antikorupsi')->nullable();
            $table->string('file_bukti_dukung')->nullable();
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();

            $table->foreign('sumber_baris_id')->references('id')->on('capaian_kebijakan_ppks')->nullOnDelete();
            $table->unique(['capaian_kinerja_id', 'pts_id'], 'ck_ppks_pts_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_kebijakan_ppks');
    }
};
