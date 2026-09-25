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
        Schema::create('pesan_kontak', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('email');
            $table->foreignId('tim_kerja_id')->nullable()->constrained('tim_kerja')->nullOnDelete();
            $table->string('gambar')->nullable();
            $table->text('pesan');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesan_kontak');
    }
};
