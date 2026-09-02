<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('skema_sertifikasi', function (Blueprint $table) {
            $table->id('id_skema');
            $table->string('judul');
            $table->string('nomor')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('skema_sertifikasi');
    }
};