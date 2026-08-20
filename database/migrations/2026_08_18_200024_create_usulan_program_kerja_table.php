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
            
            // Relasi ke IKU
            $table->foreignId('iku_id')->constrained('iku')->restrictOnDelete();
            
            // Kolom baru
            $table->string('nama_usulan');
            $table->text('deskripsi')->nullable();
            
            // Mengubah 'tahun' dari enum menjadi integer (unsignedSmallInteger)
            $table->unsignedSmallInteger('tahun');
            
            // File lampiran (dipertahankan)
            $table->string('file_kak_pdf')->nullable();
            $table->string('file_rab_pdf')->nullable();
            $table->string('file_rab_excel')->nullable();
            
            // Mengubah status lama menjadi status_validasi
            $table->enum('status_validasi', ['draft', 'menunggu_validasi', 'approved', 'rejected'])->default('draft');
            $table->foreignId('validator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tgl_validasi')->nullable();
            
            $table->text('catatan_revisi')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

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
