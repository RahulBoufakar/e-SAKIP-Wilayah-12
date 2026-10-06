<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('footer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sheet_id')->unique()->constrained('sheet')->cascadeOnDelete(); // 1:1
            $table->unsignedInteger('baris_awal');
            $table->unsignedInteger('baris_akhir');
            $table->boolean('otomatis')->default(true); // false = ditimpa admin
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer');
    }
};
