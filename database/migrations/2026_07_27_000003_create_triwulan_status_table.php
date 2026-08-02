<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('triwulan_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_anggaran_id')->constrained('tahun_anggaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('triwulan_id');
            $table->foreign('triwulan_id')->references('id')->on('triwulan')->restrictOnDelete();
            $table->enum('status', ['aktif', 'non_aktif'])->default('non_aktif');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['tahun_anggaran_id', 'triwulan_id']); // Rule R-1 scope
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('triwulan_status');
    }
};
