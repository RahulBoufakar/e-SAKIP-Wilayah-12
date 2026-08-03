<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rencana_aksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->restrictOnDelete(); // Rule R-3: berbasis IKU, bukan IKK
            $table->unsignedTinyInteger('triwulan_id');
            $table->foreign('triwulan_id')->references('id')->on('triwulan')->restrictOnDelete();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->restrictOnDelete();
            $table->longText('uraian')->nullable(); // null = belum diisi
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['iku_id', 'triwulan_id', 'tahun_anggaran_id']);
            // Rule R-4: sengaja TIDAK ada kolom/constraint apa pun yang merujuk
            // triwulan_status.status -> tidak ada gate Triwulan Aktif di sini.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rencana_aksi');
    }
};
