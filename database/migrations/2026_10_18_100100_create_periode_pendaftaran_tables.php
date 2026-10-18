<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periode_pendaftaran', function (Blueprint $table) {
            $table->id('id_periode');
            $table->string('nama');
            $table->date('tanggal_buka');
            $table->date('tanggal_tutup');

            // Batas jumlah permohonan dalam periode ini; null berarti tanpa batas.
            $table->unsignedInteger('kuota')->nullable();

            $table->text('keterangan')->nullable();

            // Admin bisa menutup periode lebih awal tanpa mengubah tanggal.
            $table->boolean('aktif')->default(true);

            $table->timestamps();
        });

        // Skema yang bisa dipilih calon asesi pada suatu periode.
        Schema::create('periode_skema', function (Blueprint $table) {
            $table->foreignId('id_periode')
                ->constrained('periode_pendaftaran', 'id_periode')
                ->cascadeOnDelete();

            $table->foreignId('id_skema')
                ->constrained('skema_sertifikasi', 'id_skema');

            $table->primary(['id_periode', 'id_skema']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_skema');
        Schema::dropIfExists('periode_pendaftaran');
    }
};
