<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // tipe_iku: penanda "IKU resmi yang mana" untuk routing ke controller/Blade
        // Capaian Kinerja Hybrid. Nullable supaya IKU di luar 9 tipe hybrid tetap valid.
        // Relasi ke Tim Kerja lewat pivot iku_tim_kerja (bisa lebih dari satu).
        Schema::create('iku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sasaran_kegiatan_id')->constrained('sasaran_kegiatan')->restrictOnDelete();
            $table->enum('jenis', ['IKU', 'IKK'])->default('IKU');
            $table->string('kode', 20);
            $table->text('deskripsi');
            $table->string('tipe_iku', 50)->nullable();
            $table->decimal('target_pk', 10, 2);
            $table->string('satuan', 20)->default('%');
            $table->text('deskripsi_target')->nullable();
            $table->timestamps();

            $table->unique(['sasaran_kegiatan_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iku');
    }
};
