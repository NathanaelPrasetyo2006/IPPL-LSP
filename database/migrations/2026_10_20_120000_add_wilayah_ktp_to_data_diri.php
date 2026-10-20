<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Wilayah alamat KTP disimpan terpisah dari wilayah domisili (kolom provinsi, kota, dst.),
 * karena keduanya bisa berbeda. Nullable karena profil lama belum punya wilayah KTP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('data_diri', function (Blueprint $table) {
            $table->string('provinsi_ktp', 100)->nullable();
            $table->string('kota_ktp', 100)->nullable();
            $table->string('kecamatan_ktp', 100)->nullable();
            $table->string('kelurahan_ktp', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('data_diri', function (Blueprint $table) {
            $table->dropColumn(['provinsi_ktp', 'kota_ktp', 'kecamatan_ktp', 'kelurahan_ktp']);
        });
    }
};
