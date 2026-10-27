<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinkronisasi_pts', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['pts', 'akreditasi']);
            $table->enum('status', ['diproses', 'berhasil', 'gagal'])->default('diproses');
            $table->foreignId('dipicu_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->json('ringkasan')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamp('mulai_at')->nullable();
            $table->timestamp('selesai_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinkronisasi_pts');
    }
};