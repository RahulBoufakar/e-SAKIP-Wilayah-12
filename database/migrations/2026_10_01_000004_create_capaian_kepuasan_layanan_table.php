<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 1 — IKU 1 (Kepuasan Layanan). Pola: SUM(puas)/SUM(total)x100%.
    public function up(): void
    {
        Schema::create('capaian_kepuasan_layanan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->unsignedInteger('total_responden');
            $table->unsignedInteger('responden_puas');
            $table->string('file_bukti_dukung')->nullable();
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_kepuasan_layanan');
    }
};
