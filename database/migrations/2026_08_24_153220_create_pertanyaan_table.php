<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pertanyaan', function (Blueprint $table) {
            $table->id('id_pertanyaan');

            $table->foreignId('id_elemen')
                ->constrained('elemen', 'id_elemen')
                ->cascadeOnDelete();

            $table->string('nomor');
            $table->text('teks_pertanyaan');
            $table->unsignedInteger('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pertanyaan');
    }
};