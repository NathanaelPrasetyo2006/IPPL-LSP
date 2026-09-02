<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_diri', function (Blueprint $table) {
            $table->id('id_data_diri');

            $table->foreignId('id_user')
                ->unique()
                ->constrained('users', 'id_user')
                ->cascadeOnDelete();

            $table->string('nama_lengkap');
            $table->string('no_telp');
            $table->string('nik')->unique();
            $table->string('file_ktp');

            $table->string('tempat_lahir');
            $table->date('tgl_lahir');
            $table->string('jenis_kelamin');

            $table->foreignId('id_negara')
                ->constrained('negara', 'id_negara');

            $table->string('pekerjaan');
            $table->text('alamat_ktp');
            $table->text('alamat_domisili');
            $table->string('provinsi');
            $table->string('kota');
            $table->string('kecamatan');
            $table->string('kelurahan');
            $table->string('kode_pos');

            $table->string('jenjang_pendidikan');
            $table->string('nama_sekolah');

            $table->string('file_pas_foto');
            $table->string('file_npwp')->nullable();
            $table->string('file_cv')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_diri');
    }
};