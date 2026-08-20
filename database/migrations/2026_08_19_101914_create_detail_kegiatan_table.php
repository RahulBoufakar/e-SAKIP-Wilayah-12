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
        Schema::create('detail_kegiatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usulan_program_kerja_id')->constrained('usulan_program_kerja')->cascadeOnDelete();
            $table->string('nama_detail');
            $table->string('tempat_pelaksanaan');
            $table->string('bentuk_kegiatan');
            $table->json('bulan_kegiatan'); // array angka bulan 1-12 (checkbox multi-pilih)
            $table->decimal('anggaran', 15, 2);
            $table->timestamps();

            // Rule: 1 Program Kerja Utama hanya boleh punya 1 Detail Kegiatan
            $table->unique('usulan_program_kerja_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_kegiatan');
    }
};
