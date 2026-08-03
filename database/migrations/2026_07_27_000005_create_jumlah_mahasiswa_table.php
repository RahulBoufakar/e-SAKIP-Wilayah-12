<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jumlah_mahasiswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->unsignedInteger('jumlah'); // FR: non-negatif
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jumlah_mahasiswa');
    }
};
