<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Sheet master = RO. Seluruh tabel struktur (sheet/header/footer/kategori/kelompok)
    // adalah hasil deteksi dari satu file_excel, jadi ikut terhapus bersama file-nya (cascade).
    public function up(): void
    {
        Schema::create('sheet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_excel_id')->constrained('file_excel')->cascadeOnDelete();
            $table->string('kode_sheet'); // judul sheet, mis. "7735.951"
            $table->string('nama_sheet');
            $table->unsignedInteger('urutan');
            $table->unsignedInteger('ro_baris_awal');
            $table->unsignedInteger('ro_baris_akhir');
            $table->string('kolom_akhir', 3); // kolom terakhir area cetak, mis. "X"

            $table->unique(['file_excel_id', 'kode_sheet']); // unik per file
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sheet');
    }
};
