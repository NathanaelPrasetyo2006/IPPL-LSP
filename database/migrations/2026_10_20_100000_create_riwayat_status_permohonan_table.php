<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Jejak perjalanan permohonan: diajukan, diterima/ditolak (dengan catatan), diajukan ulang, dibatalkan.
        // Catatan penolakan lama tetap tersimpan walau permohonan diperbaiki dan diajukan lagi.
        Schema::create('riwayat_status_permohonan', function (Blueprint $table) {
            $table->id('id_riwayat_status');

            $table->foreignId('id_permohonan')
                ->constrained('permohonan_sertifikasi', 'id_permohonan')
                ->cascadeOnDelete();

            // diajukan | diterima | ditolak | dibatalkan
            $table->string('status', 20);
            $table->text('catatan')->nullable();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users', 'id_user')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });

        // Permohonan yang sudah ada diberi riwayat dari data yang tersimpan.
        DB::table('permohonan_sertifikasi')->orderBy('id_permohonan')->get()->each(function ($p) {
            DB::table('riwayat_status_permohonan')->insert([
                'id_permohonan' => $p->id_permohonan,
                'status' => 'diajukan',
                'created_at' => $p->created_at,
            ]);

            if ($p->diverifikasi_pada) {
                DB::table('riwayat_status_permohonan')->insert([
                    'id_permohonan' => $p->id_permohonan,
                    'status' => $p->status,
                    'catatan' => $p->catatan_verifikasi,
                    'id_user' => $p->id_verifikator,
                    'created_at' => $p->diverifikasi_pada,
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_status_permohonan');
    }
};
