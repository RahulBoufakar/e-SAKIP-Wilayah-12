<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 4 — IKU 1.3 (Tata Kelola SAKIP+ZI). Tingkat institusi, tanpa pts_id. Entri tunggal per header.
    public function up(): void
    {
        Schema::create('capaian_tata_kelola', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->enum('predikat_sakip', ['AA', 'A', 'BB', 'B', 'CC', 'C', 'belum_diterbitkan']);
            $table->enum('predikat_zi', ['WBBM', 'WBK', 'menuju_wbk', 'belum_diterbitkan']);
            $table->string('file_bukti_dukung')->nullable();
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();

            $table->unique('capaian_kinerja_id', 'ck_tatakelola_tunggal_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_tata_kelola');
    }
};
