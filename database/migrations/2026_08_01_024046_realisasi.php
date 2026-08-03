<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('realisasis', function (Blueprint $table) {
            $table->id();
            $table->foreignId('iku_id')->constrained('iku')->cascadeOnDelete();
            $table->enum('triwulan', ['tw1', 'tw2', 'tw3', 'tw4']);
            $table->decimal('target', 10, 2)->nullable();
            $table->decimal('realisasi', 10, 2)->nullable();
            $table->timestamps();

            $table->unique(['iku_id', 'triwulan']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('realisasis');
    }
};
