<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ponytail-flag: ERD masih menulis users.satuan_kerja_id sebagai FK nullable
        // tunggal. Sesuai arahan terbaru, diganti tabel pivot many-to-many supaya
        // satu User (role tim_kerja) bisa ditugaskan ke lebih dari satu Satuan Kerja.
        Schema::create('user_tim_kerja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tim_kerja_id')->constrained('tim_kerja')->restrictOnDelete(); // D-6
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'tim_kerja_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tim_kerja');
    }
};
