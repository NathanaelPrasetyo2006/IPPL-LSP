<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            // Biodata dilengkapi sendiri oleh asesor setelah akunnya aktif, jadi semua nullable.
            $table->string('nik', 16)->nullable()->unique();
            $table->string('tempat_lahir')->nullable();
            $table->date('tgl_lahir')->nullable();
            $table->string('jenis_kelamin', 1)->nullable();
            $table->string('no_telp', 30)->nullable();
            $table->text('alamat')->nullable();
            $table->string('kota', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('pendidikan_terakhir', 20)->nullable();
            $table->string('instansi')->nullable();
            $table->string('bidang_keahlian')->nullable();
            $table->date('masa_berlaku_sertifikat')->nullable();
            $table->string('file_pas_foto')->nullable();
            $table->timestamp('biodata_diperbarui_pada')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->dropUnique(['nik']);
            $table->dropColumn([
                'nik',
                'tempat_lahir',
                'tgl_lahir',
                'jenis_kelamin',
                'no_telp',
                'alamat',
                'kota',
                'provinsi',
                'pendidikan_terakhir',
                'instansi',
                'bidang_keahlian',
                'masa_berlaku_sertifikat',
                'file_pas_foto',
                'biodata_diperbarui_pada',
            ]);
        });
    }
};
