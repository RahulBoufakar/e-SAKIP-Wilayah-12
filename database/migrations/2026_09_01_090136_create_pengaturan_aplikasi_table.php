<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_aplikasi', function (Blueprint $table) {
            $table->id();
            $table->string('nama_aplikasi')->default('eSAKIP LLDikti Wilayah XII');
            $table->string('logo')->nullable();
            $table->string('favicon')->nullable();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_aplikasi');
    }
};