<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Kategori = komponen ("051", "052", ...).
    public function up(): void
    {
        Schema::create('kategori', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->constrained('sheet')->cascadeOnDelete();
            $table->string('kode_kategori', 20);
            $table->string('nama_kategori');
            $table->unsignedInteger('baris_awal');
            $table->unsignedInteger('baris_akhir');
            $table->unsignedInteger('urutan');

            $table->unique(['sheet_id', 'kode_kategori']); // unik PER SHEET
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori');
    }
};
