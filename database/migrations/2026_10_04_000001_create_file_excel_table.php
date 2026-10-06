<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // RAB Generator 03-erd-basis-data.md. Hanya satu baris `aktif = true` pada satu waktu:
    // ditegakkan lewat FileExcel::aktifkan() (MySQL tidak punya partial unique index).
    public function up(): void
    {
        Schema::create('file_excel', function (Blueprint $table) {
            $table->id();
            $table->string('nama_file');
            $table->string('path'); // disk 'private'
            $table->char('hash_sha256', 64);
            $table->boolean('aktif')->default(false);
            $table->timestamp('tanggal_upload')->useCurrent();
            $table->foreignId('diunggah_oleh')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_excel');
    }
};
