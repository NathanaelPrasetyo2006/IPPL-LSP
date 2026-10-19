<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lokasi TUK pada suatu periode beserta skema yang diujikan di sana.
        // Satu baris = satu skema di satu lokasi, jadi lokasi tanpa skema tidak mungkin tersimpan.
        Schema::create('periode_lokasi', function (Blueprint $table) {
            $table->unsignedBigInteger('id_periode');
            $table->unsignedBigInteger('id_skema');

            $table->foreignId('id_tuk')->constrained('tuk', 'id_tuk');

            $table->primary(['id_periode', 'id_tuk', 'id_skema']);

            // Skema di lokasi wajib termasuk skema yang dibuka periode; ikut terhapus saat skema dilepas dari periode.
            $table->foreign(['id_periode', 'id_skema'])
                ->references(['id_periode', 'id_skema'])
                ->on('periode_skema')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periode_lokasi');
    }
};
