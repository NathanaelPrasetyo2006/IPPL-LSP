<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kode asesor otomatis (ASR-tahun-urut), setara kode asesi; No. MET tetap nomor resmi dari BNSP.
        Schema::table('asesor', function (Blueprint $table) {
            $table->string('kode_asesor', 20)->nullable()->unique()->after('id_asesor');
        });

        // Asesor yang sudah ada langsung diberi kode; tahunnya mengikuti tanggal akun dibuat.
        DB::table('asesor')
            ->leftJoin('users', 'users.id_user', '=', 'asesor.id_user')
            ->orderBy('asesor.id_asesor')
            ->get(['asesor.id_asesor', 'users.created_at'])
            ->each(fn ($baris) => DB::table('asesor')->where('id_asesor', $baris->id_asesor)->update([
                'kode_asesor' => sprintf('ASR-%s-%05d', $baris->created_at ? date('Y', strtotime($baris->created_at)) : date('Y'), $baris->id_asesor),
            ]));
    }

    public function down(): void
    {
        Schema::table('asesor', function (Blueprint $table) {
            $table->dropUnique(['kode_asesor']);
            $table->dropColumn('kode_asesor');
        });
    }
};
