<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.2 — pola mirip jumlah_pts/jumlah_mahasiswa. Denominator IKU 3.3.
    // Pembagi kini per header capaian (per triwulan), bukan per tahun anggaran.
    // tahun_anggaran_id dipertahankan; kini REDUNDAN (bisa diturunkan dari header capaian_kinerja).
    public function up(): void
    {
        Schema::create('jumlah_publikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamp('created_at')->useCurrent();
            $table->foreignId('capaian_kinerja_id')->nullable()->unique()->constrained('capaian_kinerja')->cascadeOnDelete();
            $table->foreignId('diperbarui_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumlah_publikasi');
    }
};
