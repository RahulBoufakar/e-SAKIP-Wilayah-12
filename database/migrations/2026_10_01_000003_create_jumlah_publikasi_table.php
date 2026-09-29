<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Spek §4.2 — pola identik jumlah_pts/jumlah_mahasiswa. Denominator IKU 8.
    public function up(): void
    {
        Schema::create('jumlah_publikasi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumlah_publikasi');
    }
};
