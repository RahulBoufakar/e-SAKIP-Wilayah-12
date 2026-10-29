<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.3 tabel 10 — IKU 9. Nilai langsung, tanpa pembagian (§5 Pola 6). Entri tunggal per header.
    public function up(): void
    {
        Schema::create('capaian_nilai_rka', function (Blueprint $table) {
            $table->id();
            $table->foreignId('capaian_kinerja_id')->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->decimal('nilai_rka', 10, 2);
            $table->string('file_bukti_dukung')->nullable();
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamps();

            $table->unique('capaian_kinerja_id', 'ck_nilairka_tunggal_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('capaian_nilai_rka');
    }
};
