<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // PRD §5.1: append-only history — tidak ada unique constraint pada
        // kombinasi jenis+periode karena generate ulang membuat versi baru,
        // bukan menimpa (§4.7).
        Schema::create('laporan_kinerja', function (Blueprint $table) {
            $table->id();
            $table->enum('jenis', ['bulanan', 'triwulanan', 'tahunan']);
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->tinyInteger('bulan')->nullable(); // diisi hanya jika jenis = bulanan (1-12)
            $table->unsignedTinyInteger('triwulan_id')->nullable(); // diisi hanya jika jenis = triwulanan
            $table->foreign('triwulan_id')->references('id')->on('triwulan')->restrictOnDelete();
            $table->unsignedInteger('versi')->default(1);
            $table->enum('status', ['diproses', 'berhasil', 'gagal'])->default('diproses');
            $table->string('file_path')->nullable(); // path di disk privat 'laporan'
            $table->text('catatan')->nullable(); // pesan error, atau ringkasan data tidak lengkap
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete(); // null = otomatis
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_kinerja');
    }
};
