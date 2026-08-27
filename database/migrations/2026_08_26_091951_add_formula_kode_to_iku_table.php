<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('iku', function (Blueprint $table) {
            // Dipilih manual oleh Admin dari dropdown — bukan diturunkan dari
            // 'kode' auto-generate, karena 'kode' bisa berubah urutannya.
            $table->string('formula_kode')->nullable()->after('deskripsi_target');
        });
    }

    public function down(): void
    {
        Schema::table('iku', function (Blueprint $table) {
            $table->dropColumn('formula_kode');
        });
    }
};