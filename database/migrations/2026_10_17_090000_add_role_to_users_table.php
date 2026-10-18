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
            // Default asesi supaya akun hasil registrasi mandiri tidak pernah
            // mendapat hak akses lebih tinggi secara tidak sengaja.
            $table->string('role', 20)->default('asesi')->index();
        });

        // Akun yang sudah ada: yang tertaut ke tabel asesor menjadi asesor,
        // sisanya yang tidak punya data diri asesi dianggap akun admin.
        $asesorUsers = DB::table('asesor')->select('id_user');
        $asesiUsers = DB::table('data_diri')->select('id_user');

        DB::table('users')->whereIn('id_user', $asesorUsers)->update(['role' => 'asesor']);

        DB::table('users')
            ->whereNotIn('id_user', $asesorUsers)
            ->whereNotIn('id_user', $asesiUsers)
            ->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
