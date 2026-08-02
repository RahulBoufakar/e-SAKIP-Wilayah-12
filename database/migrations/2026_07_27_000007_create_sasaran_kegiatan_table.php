<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sasaran_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->string('kode', 20); // auto-generate "s.1", "s.2"... reset per tahun (D-2)
            $table->text('nama_sasaran');
            $table->timestamps();

            $table->unique(['tahun_anggaran_id', 'kode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sasaran_kegiatan');
    }
};
