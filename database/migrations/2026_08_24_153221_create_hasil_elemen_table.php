<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hasil_elemen', function (Blueprint $table) {
            $table->id('id_hasil');

            $table->foreignId('id_asesmen')
                ->constrained('asesmen_mandiri', 'id_asesmen')
                ->cascadeOnDelete();

            $table->foreignId('id_elemen')
                ->constrained('elemen', 'id_elemen')
                ->cascadeOnDelete();

            $table->string('penilaian');

            $table->string('bukti_kompetensi')
                ->nullable();

            $table->boolean('v')
                ->default(false);

            $table->boolean('a')
                ->default(false);

            $table->boolean('t')
                ->default(false);

            $table->boolean('m')
                ->default(false);

            $table->unique(
                ['id_asesmen', 'id_elemen'],
                'unique_hasil_elemen'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hasil_elemen');
    }
};