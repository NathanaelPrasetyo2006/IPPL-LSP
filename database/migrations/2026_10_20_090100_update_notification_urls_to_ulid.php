<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Notifikasi lonceng yang sudah tersimpan masih memakai id permohonan di URL-nya.
 * Setelah URL beralih ke ulid, alamat lama itu diganti supaya notifikasi tetap bisa dibuka.
 */
return new class extends Migration
{
    private const POLA = '#^/(sertifikasi/permohonan|pendaftaran/verifikasi)/(\d+)$#';

    public function up(): void
    {
        $ulid = DB::table('permohonan_sertifikasi')->pluck('ulid', 'id_permohonan');

        DB::table('notifications')->orderBy('id')->get(['id', 'data'])->each(function ($notifikasi) use ($ulid) {
            $data = json_decode($notifikasi->data, true);

            if (! is_string($data['url'] ?? null) || ! preg_match(self::POLA, $data['url'], $cocok) || ! isset($ulid[$cocok[2]])) {
                return;
            }

            $data['url'] = "/{$cocok[1]}/{$ulid[$cocok[2]]}";

            DB::table('notifications')->where('id', $notifikasi->id)->update(['data' => json_encode($data)]);
        });
    }

    public function down(): void
    {
        // Tidak dikembalikan: URL berbasis id sudah tidak dilayani lagi.
    }
};
