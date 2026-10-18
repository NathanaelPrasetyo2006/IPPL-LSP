<?php

namespace App\Support;

use App\Enums\JenisDokumen;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Penyimpanan dokumen asesi di disk privat (storage/app/private).
 *
 * Struktur folder (dibuat otomatis oleh Laravel saat file pertama disimpan):
 *   asesi/{kode_asesi}_{nama}/identitas/ktp_20261018-101500_ab12.jpg
 *   asesi/{kode_asesi}_{nama}/apl-01/{nomor_permohonan}/ijazah_20261018-101500_cd34.pdf
 */
class PenyimpananDokumen
{
    public const DISK = 'local';

    /**
     * Simpan file dengan nama yang mudah dibaca: jenis + waktu unggah + akhiran acak
     * (supaya unggahan ulang tidak menimpa file lama sebelum data tersimpan).
     */
    public static function simpan(UploadedFile $file, string $folder, JenisDokumen $jenis): string
    {
        $nama = sprintf('%s_%s_%s.%s', $jenis->value, now()->format('Ymd-His'), Str::lower(Str::random(4)), $file->extension());

        return $file->storeAs($folder, $nama, self::DISK);
    }

    /**
     * @param  list<string|null>  $paths
     */
    public static function hapus(array $paths): void
    {
        $paths = array_values(array_filter($paths));

        if ($paths !== []) {
            Storage::disk(self::DISK)->delete($paths);
        }
    }

    /**
     * Tampilkan file di browser (inline) dengan nama unduhan yang rapi.
     */
    public static function tampilkan(string $path, string $namaUnduhan): StreamedResponse
    {
        abort_unless(Storage::disk(self::DISK)->exists($path), 404, 'File dokumen tidak ditemukan.');

        return Storage::disk(self::DISK)->response($path, $namaUnduhan, [
            'Cache-Control' => 'private, max-age=600',
        ]);
    }
}
