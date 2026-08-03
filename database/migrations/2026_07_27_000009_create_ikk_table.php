<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ikk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->restrictOnDelete();
            $table->foreignId('tim_kerja_id')->constrained('tim_kerja')->restrictOnDelete();
            $table->string('kode', 20); // auto-generate "{kode_iku}.{urutan_ikk}" e.g. "1.1.1"
            $table->text('deskripsi');
            $table->decimal('target', 10, 2); // target numerik, misal 100, 50.5, dst. (bukan teks)
            $table->string('satuan', 50); // teks bebas: "Nilai", "Dokumen", dst.
            $table->text('deskripsi_target')->nullable(); 
            $table->timestamps();

            // Sama seperti iku.kode: discope per iku_id, bukan global (lihat catatan di migration iku).
            $table->unique(['iku_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ikk');
    }
};
