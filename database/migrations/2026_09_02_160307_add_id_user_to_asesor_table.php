<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            // Akun login asesor, mengikuti pola relasi data_diri ke users.
            $table->foreignId('id_user')
                ->unique()
                ->constrained('users', 'id_user')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_user');
        });
    }
};
