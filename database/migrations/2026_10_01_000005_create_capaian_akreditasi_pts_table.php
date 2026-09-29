<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 2 — IKU 2 komponen 1 (Akreditasi PTS).
    public function up(): void
    {
        Schema::create('capaian_akreditasi_pts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->string('akreditasi');
            $table->string('no_sk');
            $table->date('masa_berlaku');
            $table->string('file_bukti_dukung')->nullable();
            $table->enum('sumber', ['manual', 'pddikti'])->default('manual');
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_akreditasi_pts');
    }
};
