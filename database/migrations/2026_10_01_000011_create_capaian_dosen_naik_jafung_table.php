<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 8 — IKU 7. nidn dipakai dedup COUNT DISTINCT (§5 Pola 5). Bukti WAJIB (SK Jafung).
    public function up(): void
    {
        Schema::create('capaian_dosen_naik_jafung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->string('nama_dosen');
            $table->string('nidn');
            $table->enum('jenjang_asal', ['asisten_ahli', 'lektor', 'lektor_kepala']);
            $table->enum('jenjang_baru', ['lektor', 'lektor_kepala', 'profesor']);
            $table->string('no_sk');
            $table->date('tanggal_sk');
            $table->string('file_bukti_dukung');
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();

            $table->index('nidn');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_dosen_naik_jafung');
    }
};
