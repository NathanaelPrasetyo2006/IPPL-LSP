<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesmen_mandiri', function (Blueprint $table) {
            $table->id('id_asesmen');

            $table->foreignId('id_skema')
                ->constrained('skema_sertifikasi', 'id_skema')
                ->cascadeOnDelete();

            $table->foreignId('id_asesor')
                ->constrained('asesor', 'id_asesor');

            $table->foreignId('id_data_diri')
                ->constrained('data_diri', 'id_data_diri')
                ->cascadeOnDelete();

            $table->string('tuk');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->string('rekomendasi_lanjut')->nullable();

            $table->boolean('lanjut_portofolio')
                ->default(false);

            $table->boolean('lanjut_uji_kompetensi')
                ->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesmen_mandiri');
    }
};