<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_laporan_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporan_kegiatan')->cascadeOnDelete();
            $table->string('nama_dokumen');
            $table->string('file_dokumen')->nullable();
            $table->enum('status_validasi', ['belum_diunggah', 'menunggu_validasi', 'disetujui', 'ditolak'])
                ->default('belum_diunggah');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_laporan_kegiatan');
    }
};