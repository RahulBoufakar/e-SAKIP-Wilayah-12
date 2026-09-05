<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rule: master lookup fixed 2 jenis (RAB, KAK/TOR), diseed sekali
        // (TemplateDokumenSeeder), tidak ada endpoint create/delete dari UI
        // — sama seperti pola tabel `triwulan`.
        Schema::create('template_dokumen', function (Blueprint $table) {
            $table->id();
            $table->enum('kode', ['rab_excel','rab_pdf', 'kak_tor']);
            $table->string('nama');
            $table->string('file')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique('kode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_dokumen');
    }
};