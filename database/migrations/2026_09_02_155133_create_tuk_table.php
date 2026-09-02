<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tuk', function (Blueprint $table) {
            $table->id('id_tuk');
            $table->string('kode')->unique();
            $table->string('nama');

            // sewaktu | tempat_kerja | mandiri
            $table->string('jenis');

            $table->string('alamat');
            $table->string('kota');
            $table->string('penanggung_jawab')->nullable();
            $table->string('telepon')->nullable();

            // aktif | nonaktif
            $table->string('status')->default('aktif');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tuk');
    }
};
