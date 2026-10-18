<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Akun asesor baru aktif setelah pemilik email mengklik link verifikasi.
            $table->timestamp('email_verified_at')->nullable();
        });

        // Akun yang sudah ada sebelumnya sudah dipakai, jadi dianggap terverifikasi.
        DB::table('users')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_verified_at');
        });
    }
};
