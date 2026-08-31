<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pts', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pts', 20)->unique();
            $table->string('nama_pts', 255);
            $table->enum('status_pts', ['aktif', 'alih_bentuk', 'tutup', 'alih_kelola', 'pembinaan'])->default('aktif');
            $table->enum('akreditasi_pts', ['unggul', 'terakreditasi', 'tidak_terakreditasi'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pts');
    }
};