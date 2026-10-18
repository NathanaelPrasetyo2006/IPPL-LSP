<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu formulir FR-APL-01 yang diajukan calon asesi.
        Schema::create('permohonan_sertifikasi', function (Blueprint $table) {
            $table->id('id_permohonan');

            // Nomor formulir, mis. APL-2026-00001. Diisi setelah insert karena memakai id.
            $table->string('nomor', 20)->nullable()->unique();

            $table->foreignId('id_data_diri')
                ->constrained('data_diri', 'id_data_diri');

            $table->foreignId('id_periode')
                ->constrained('periode_pendaftaran', 'id_periode');

            $table->foreignId('id_skema')
                ->constrained('skema_sertifikasi', 'id_skema');

            // sertifikasi | sertifikasi_ulang | pkt | rpl | lainnya
            $table->string('tujuan_asesmen', 30);
            $table->string('tujuan_asesmen_lainnya')->nullable();

            // diajukan | diterima | ditolak
            $table->string('status', 20)->default('diajukan')->index();

            // Hasil cek kelengkapan per jenis dokumen: {"ktp": true, "ijazah": false, ...}
            $table->json('cek_dokumen')->nullable();
            $table->text('catatan_verifikasi')->nullable();

            $table->foreignId('id_verifikator')
                ->nullable()
                ->constrained('users', 'id_user')
                ->nullOnDelete();

            $table->timestamp('diverifikasi_pada')->nullable();
            $table->timestamps();
        });

        // Bukti persyaratan dasar (Bagian 3 APL-01) yang diunggah per permohonan.
        Schema::create('dokumen_permohonan', function (Blueprint $table) {
            $table->id('id_dokumen');

            $table->foreignId('id_permohonan')
                ->constrained('permohonan_sertifikasi', 'id_permohonan')
                ->cascadeOnDelete();

            $table->string('jenis', 40);
            $table->string('nama_asli');
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('ukuran');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['id_permohonan', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_permohonan');
        Schema::dropIfExists('permohonan_sertifikasi');
    }
};
