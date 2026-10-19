<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Kode acak (ULID) untuk dipakai di URL menggantikan id berurutan, supaya data tidak bisa ditebak
 * dengan mengganti angka di alamat halaman. Primary key tetap angka sehingga relasi tidak berubah.
 */
return new class extends Migration
{
    /**
     * Tabel => primary key.
     *
     * @var array<string, string>
     */
    private const TABEL = [
        'users' => 'id_user',
        'asesor' => 'id_asesor',
        'skema_sertifikasi' => 'id_skema',
        'unit_kompetensi' => 'id_unit_kompetensi',
        'tuk' => 'id_tuk',
        'periode_pendaftaran' => 'id_periode',
        'permohonan_sertifikasi' => 'id_permohonan',
    ];

    public function up(): void
    {
        foreach (self::TABEL as $tabel => $primary) {
            Schema::table($tabel, fn (Blueprint $table) => $table->ulid('ulid')->nullable());

            // Data yang sudah ada diberi kode sebelum indeks unik dipasang.
            DB::table($tabel)->orderBy($primary)->pluck($primary)->each(
                fn ($id) => DB::table($tabel)->where($primary, $id)->update(['ulid' => Str::lower((string) Str::ulid())]),
            );

            // Sengaja tanpa ->change() ke NOT NULL: di SQLite (testing) change() membangun ulang tabel
            // dan merusak partial unique index (mis. nomor skema yang boleh dipakai ulang setelah dihapus).
            // Kolom tetap selalu terisi karena dibuat otomatis oleh model (trait PunyaKodeUrl).
            Schema::table($tabel, fn (Blueprint $table) => $table->unique('ulid'));
        }
    }

    public function down(): void
    {
        foreach (array_keys(self::TABEL) as $tabel) {
            Schema::table($tabel, function (Blueprint $table) {
                $table->dropUnique(['ulid']);
                $table->dropColumn('ulid');
            });
        }
    }
};
