<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu IKU/IKK bisa ditangani lebih dari satu Tim Kerja sekaligus.
     */
    public function up(): void
    {
        Schema::create('iku_tim_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->restrictOnDelete();
            $table->foreignId('tim_kerja_id')->constrained('tim_kerja')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['iku_id', 'tim_kerja_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iku_tim_kerja');
    }
};
