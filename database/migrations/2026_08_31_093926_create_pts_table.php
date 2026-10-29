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
            $table->string('provinsi', 100)->nullable();
            $table->enum('status_pts', ['aktif', 'non_aktif', 'alih_bentuk', 'tutup', 'alih_kelola', 'pembinaan'])->nullable()->default(null);
            $table->enum('akreditasi_pts', ['unggul', 'terakreditasi', 'tidak_terakreditasi'])->nullable();
            $table->enum('sumber_data', ['manual', 'lldikti12', 'pddikti'])->default('manual');
            $table->timestamp('terakhir_sinkron_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pts');
    }
};
