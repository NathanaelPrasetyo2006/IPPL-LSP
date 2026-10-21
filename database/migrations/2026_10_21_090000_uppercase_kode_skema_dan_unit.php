<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kode skema dan kode unit kini selalu disimpan berhuruf besar (seperti No. MET).
 * Data lama diseragamkan; baris yang huruf besarnya bentrok dengan kode lain dibiarkan
 * supaya migrasi tidak gagal, dan bisa dirapikan admin lewat form ubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            update skema_sertifikasi
            set nomor = upper(nomor)
            where nomor <> upper(nomor)
              and not exists (
                  select 1 from skema_sertifikasi lain
                  where lain.id_skema <> skema_sertifikasi.id_skema and lain.nomor = upper(skema_sertifikasi.nomor)
              )
        SQL);

        DB::statement(<<<'SQL'
            update unit_kompetensi
            set kode = upper(kode)
            where kode <> upper(kode)
              and not exists (
                  select 1 from unit_kompetensi lain
                  where lain.id_unit_kompetensi <> unit_kompetensi.id_unit_kompetensi
                    and lain.id_skema = unit_kompetensi.id_skema
                    and lain.deleted_at is null
                    and lain.kode = upper(unit_kompetensi.kode)
              )
        SQL);
    }

    public function down(): void
    {
        // Kapitalisasi asli tidak tersimpan, jadi tidak bisa dikembalikan.
    }
};
