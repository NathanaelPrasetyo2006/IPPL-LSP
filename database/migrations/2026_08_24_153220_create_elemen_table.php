<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('elemen', function (Blueprint $table) {
            $table->id('id_elemen');

            $table->foreignId('id_unit_kompetensi')
                ->constrained('unit_kompetensi', 'id_unit_kompetensi')
                ->cascadeOnDelete();

            $table->string('nama_elemen');
            $table->unsignedInteger('urutan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elemen');
    }
};