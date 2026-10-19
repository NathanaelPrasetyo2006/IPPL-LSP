<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Master TUK cukup berisi nama lokasi dan detail alamat (hasil rapat revisi sprint 1).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kota digabung ke alamat dulu supaya informasinya tidak hilang saat kolomnya dibuang.
        DB::table('tuk')->update(['alamat' => DB::raw("alamat || ', ' || kota")]);

        // Partial unique index harus dibuang sebelum kolomnya (SQLite menolak drop kolom yang masih ter-index).
        DB::statement('drop index if exists tuk_kode_unique');

        Schema::table('tuk', function (Blueprint $table) {
            $table->dropColumn(['kode', 'jenis', 'kota', 'penanggung_jawab', 'telepon', 'status']);
        });

        Schema::table('tuk', function (Blueprint $table) {
            $table->text('alamat')->change();
        });
    }

    public function down(): void
    {
        Schema::table('tuk', function (Blueprint $table) {
            $table->string('alamat')->change();

            // Data lama tidak bisa dipulihkan; kolom dibuat nullable agar baris yang ada tetap valid.
            $table->string('kode')->nullable();
            $table->string('jenis')->nullable();
            $table->string('kota')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->string('telepon')->nullable();
            $table->string('status')->default('aktif');
        });

        DB::statement('create unique index tuk_kode_unique on tuk (kode) where deleted_at is null');
    }
};
