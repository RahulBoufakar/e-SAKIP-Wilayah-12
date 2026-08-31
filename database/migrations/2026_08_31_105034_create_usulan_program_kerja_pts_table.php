<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usulan_program_kerja_pts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_program_kerja_id')->constrained('usulan_program_kerja')->cascadeOnDelete();
            $table->foreignId('pts_id')->constrained('pts')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['usulan_program_kerja_id', 'pts_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usulan_program_kerja_pts');
    }
};