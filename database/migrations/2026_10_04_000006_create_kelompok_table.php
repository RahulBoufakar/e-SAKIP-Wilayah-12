<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kelompok = sub ("A", "B", ...).
    // SENGAJA tanpa unique pada kode_kelompok: sub "A" muncul di tiap komponen, dan satu
    // komponen bisa memuat dua sub berlabel sama (lihat 03 & 07 Q4). Kunci selalu `id`.
    public function up(): void
    {
        Schema::create('kelompok', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_id')->constrained('kategori')->cascadeOnDelete();
            $table->string('kode_kelompok', 20);
            $table->string('nama_kelompok')->nullable();
            $table->unsignedInteger('baris_awal');
            $table->unsignedInteger('baris_akhir');
            $table->unsignedInteger('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelompok');
    }
};
