<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_kompetensi', function (Blueprint $table) {
            $table->id('id_unit_kompetensi');

            $table->foreignId('id_skema')
                ->constrained('skema_sertifikasi', 'id_skema')
                ->cascadeOnDelete();

            $table->string('kode');
            $table->string('judul');
            $table->unsignedInteger('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_kompetensi');
    }
};