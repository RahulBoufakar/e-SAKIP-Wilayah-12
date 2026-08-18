<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Shell table: hanya kolom workflow (status pengiriman). Kolom konten
        // spesifik modul ditambahkan lewat migration ALTER terpisah saat
        // halaman Usulan Program Kerja dibangun.
        Schema::create('usulan_program_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->restrictOnDelete();
            $table->unsignedTinyInteger('triwulan_id');
            $table->foreign('triwulan_id')->references('id')->on('triwulan')->restrictOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->enum('status', ['draft', 'menunggu_validasi', 'disetujui', 'ditolak'])->default('draft');
            $table->text('catatan_revisi')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['iku_id', 'triwulan_id', 'tahun_anggaran_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('usulan_program_kerja');
    }
};
