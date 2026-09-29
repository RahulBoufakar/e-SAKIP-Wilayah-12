<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 5 — IKU 4 (Fasilitasi Mutu PTS). Bukti dukung WAJIB.
    public function up(): void
    {
        Schema::create('capaian_fasilitasi_mutu_pts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->string('bentuk_fasilitasi');
            $table->date('tanggal_kegiatan');
            $table->string('file_bukti_dukung');
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_fasilitasi_mutu_pts');
    }
};
