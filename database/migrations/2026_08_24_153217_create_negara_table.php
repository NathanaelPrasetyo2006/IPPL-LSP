<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('negara', function (Blueprint $table) {
            $table->id('id_negara');
            $table->string('nama_negara');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('negara');
    }
};