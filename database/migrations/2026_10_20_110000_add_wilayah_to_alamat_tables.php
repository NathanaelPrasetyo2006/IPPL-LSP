<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Semua isian alamat memakai dropdown wilayah (provinsi → kab/kota → kecamatan → kelurahan),
 * jadi tiap alamat butuh empat kolom wilayah. Kolom alamat yang lama tetap dipakai untuk
 * detail jalan. Semua nullable karena data lama belum punya wilayah terpisah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->string('kecamatan', 100)->nullable();
            $table->string('kelurahan', 100)->nullable();
        });

        Schema::table('tuk', function (Blueprint $table) {
            $table->string('provinsi', 100)->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kelurahan', 100)->nullable();
        });

        Schema::table('data_diri', function (Blueprint $table) {
            $table->string('provinsi_kantor', 100)->nullable();
            $table->string('kota_kantor', 100)->nullable();
            $table->string('kecamatan_kantor', 100)->nullable();
            $table->string('kelurahan_kantor', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->dropColumn(['kecamatan', 'kelurahan']);
        });

        Schema::table('tuk', function (Blueprint $table) {
            $table->dropColumn(['provinsi', 'kota', 'kecamatan', 'kelurahan']);
        });

        Schema::table('data_diri', function (Blueprint $table) {
            $table->dropColumn(['provinsi_kantor', 'kota_kantor', 'kecamatan_kantor', 'kelurahan_kantor']);
        });
    }
};
