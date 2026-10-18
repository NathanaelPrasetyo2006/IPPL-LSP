<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom unik yang hanya berlaku untuk data yang belum dihapus,
     * supaya kode milik data terhapus boleh dipakai ulang.
     *
     * @var array<string, string>
     */
    private const UNIQUE_COLUMNS = [
        'skema_sertifikasi' => 'nomor',
        'tuk' => 'kode',
    ];

    public function up(): void
    {
        foreach (['skema_sertifikasi', 'unit_kompetensi', 'tuk'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->softDeletes());
        }

        foreach (self::UNIQUE_COLUMNS as $table => $column) {
            $index = "{$table}_{$column}_unique";

            Schema::table($table, fn (Blueprint $table) => $table->dropUnique($index));

            // Partial index didukung PostgreSQL dan SQLite (dipakai saat testing).
            DB::statement("create unique index {$index} on {$table} ({$column}) where deleted_at is null");
        }
    }

    public function down(): void
    {
        foreach (self::UNIQUE_COLUMNS as $table => $column) {
            $index = "{$table}_{$column}_unique";

            Schema::table($table, function (Blueprint $table) use ($index, $column) {
                $table->dropIndex($index);
                $table->unique($column);
            });
        }

        foreach (['skema_sertifikasi', 'unit_kompetensi', 'tuk'] as $table) {
            Schema::table($table, fn (Blueprint $table) => $table->dropSoftDeletes());
        }
    }
};
