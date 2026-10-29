<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 8 — IKU 3.2. nidn (NIDN/NUPTK, maks 16) dipakai dedup COUNT DISTINCT (§5 Pola 5).
    // Bukti WAJIB (SK Jafung). tenaga_pengajar/asisten_ahli = dosen yang pertama kali naik jafung.
    public function up(): void
    {
        Schema::create('capaian_dosen_naik_jafung', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            // Id baris asal (triwulan sebelumnya). Null = baris mandiri / tautan diputus.
            $table->unsignedBigInteger('sumber_baris_id')->nullable();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->string('nama_dosen');
            $table->string('nidn', 16);
            $table->enum('tipe_kepegawaian', ['Dosen Tetap Yayasan', 'Dosen PNS'])->nullable();
            $table->enum('jenjang_asal', ['tenaga_pengajar', 'asisten_ahli', 'lektor', 'lektor_kepala']);
            $table->enum('jenjang_baru', ['asisten_ahli', 'lektor', 'lektor_kepala', 'profesor']);
            $table->string('no_sk');
            $table->date('tanggal_sk');
            $table->string('file_bukti_dukung');
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();

            $table->index('nidn');
            $table->foreign('sumber_baris_id')->references('id')->on('capaian_dosen_naik_jafung')->nullOnDelete();
            $table->unique(['capaian_kinerja_id', 'nidn'], 'ck_jafung_nidn_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_dosen_naik_jafung');
    }
};
