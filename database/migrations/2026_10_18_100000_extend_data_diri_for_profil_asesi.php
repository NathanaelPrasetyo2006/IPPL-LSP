<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom yang sebelumnya wajib. Sekarang data diri dibuat saat registrasi (nama & no. telp saja)
     * lalu dilengkapi asesi lewat form APL-01, jadi kolom ini boleh kosong sementara.
     *
     * @var list<string>
     */
    private const DILENGKAPI_SAAT_APL01 = [
        'nik',
        'file_ktp',
        'tempat_lahir',
        'jenis_kelamin',
        'pekerjaan',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'kode_pos',
        'jenjang_pendidikan',
        'nama_sekolah',
        'file_pas_foto',
    ];

    public function up(): void
    {
        Schema::table('data_diri', function (Blueprint $table) {
            foreach (self::DILENGKAPI_SAAT_APL01 as $kolom) {
                $table->string($kolom)->nullable()->change();
            }

            $table->text('alamat_ktp')->nullable()->change();
            $table->text('alamat_domisili')->nullable()->change();
            $table->date('tgl_lahir')->nullable()->change();
            $table->unsignedBigInteger('id_negara')->nullable()->change();

            // Nomor induk asesi, mis. ASI-2026-00001.
            $table->string('kode_asesi', 20)->nullable()->unique();

            // Folder dokumen dikunci saat pertama dibuat supaya tidak terpecah bila nama diubah.
            $table->string('folder_dokumen')->nullable();

            // Bagian 1b FR-APL-01: data pekerjaan sekarang (opsional).
            $table->string('nama_institusi')->nullable();
            $table->string('jabatan')->nullable();
            $table->text('alamat_kantor')->nullable();
            $table->string('kode_pos_kantor', 10)->nullable();
            $table->string('telp_kantor', 30)->nullable();
            $table->string('email_kantor')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Kolom yang dibuat nullable tidak dikembalikan ke NOT NULL karena data
        // profil yang belum lengkap akan membuat rollback gagal.
        Schema::table('data_diri', function (Blueprint $table) {
            $table->dropUnique(['kode_asesi']);
            $table->dropColumn([
                'kode_asesi',
                'folder_dokumen',
                'nama_institusi',
                'jabatan',
                'alamat_kantor',
                'kode_pos_kantor',
                'telp_kantor',
                'email_kantor',
                'created_at',
                'updated_at',
            ]);
        });
    }
};
