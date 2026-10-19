<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kode OTP email untuk lupa password, aktivasi ulang akun, dan ganti email.
        // Satu baris per akun per tujuan; baris tetap ada setelah kode dipakai supaya batas kirim harian terhitung.
        Schema::create('kode_otp', function (Blueprint $table) {
            $table->id('id_kode_otp');

            $table->foreignId('id_user')
                ->constrained('users', 'id_user')
                ->cascadeOnDelete();

            // reset_password | aktivasi_akun | ganti_email
            $table->string('tujuan', 30);

            // Disimpan dalam bentuk hash; null berarti kode sudah dipakai atau dibatalkan.
            $table->string('kode_hash')->nullable();
            $table->timestamp('kedaluwarsa_pada')->nullable();
            $table->unsignedTinyInteger('percobaan_salah')->default(0);

            // Penghitung pengiriman per hari (zona waktu WIB).
            $table->date('tanggal_kirim')->nullable();
            $table->unsignedTinyInteger('jumlah_kirim')->default(0);
            $table->timestamp('terakhir_kirim_pada')->nullable();

            $table->unique(['id_user', 'tujuan']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Soft delete akun asesi: data tetap ada, akun bisa diaktifkan kembali lewat OTP.
            $table->timestamp('nonaktif_pada')->nullable();

            // Email baru yang menunggu verifikasi OTP; email lama tetap dipakai sampai terverifikasi.
            $table->string('email_baru')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nonaktif_pada', 'email_baru']);
        });

        Schema::dropIfExists('kode_otp');
    }
};
