<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lokasi uji pilihan asesi. Null berarti asesi perlu memilih (ulang) lokasi,
        // mis. karena lokasinya dihapus admin dari periode.
        Schema::table('permohonan_sertifikasi', function (Blueprint $table) {
            $table->foreignId('id_tuk')
                ->nullable()
                ->after('id_skema')
                ->constrained('tuk', 'id_tuk');
        });

        // Jejak setiap perubahan lokasi permohonan: dipilih asesi, dikosongkan admin, dipilih ulang.
        Schema::create('riwayat_lokasi_permohonan', function (Blueprint $table) {
            $table->id('id_riwayat');

            $table->foreignId('id_permohonan')
                ->constrained('permohonan_sertifikasi', 'id_permohonan')
                ->cascadeOnDelete();

            $table->foreignId('id_tuk_lama')->nullable()->constrained('tuk', 'id_tuk');
            $table->foreignId('id_tuk_baru')->nullable()->constrained('tuk', 'id_tuk');
            $table->string('keterangan');

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users', 'id_user')
                ->nullOnDelete();

            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_lokasi_permohonan');

        Schema::table('permohonan_sertifikasi', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_tuk');
        });
    }
};
