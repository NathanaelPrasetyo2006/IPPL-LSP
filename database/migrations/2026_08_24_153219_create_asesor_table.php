<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asesor', function (Blueprint $table) {
            $table->id('id_asesor');
            $table->string('nama');
            $table->string('no_reg')->unique();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asesor');
    }
};