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
        Schema::table('detail_kegiatan', function (Blueprint $table) {
            $table->enum('jenis_kegiatan', ['kunjungan_lapangan', 'lainnya'])
                ->nullable()
                ->after('bentuk_kegiatan');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_kegiatan', function (Blueprint $table) {
            $table->dropColumn('jenis_kegiatan');
        });
    }
};
