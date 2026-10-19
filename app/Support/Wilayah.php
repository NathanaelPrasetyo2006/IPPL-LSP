<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Data wilayah Indonesia (provinsi → kab/kota → kecamatan → kelurahan) dari API publik wilayah.id,
 * yang sudah memuat 38 provinsi hasil pemekaran terbaru. Hasil disimpan di cache supaya API luar
 * cukup dipanggil sekali. Kode wilayah berformat Kemendagri, mis. 35.78.01.
 */
class Wilayah
{
    private const CACHE_HARI = 30;

    /**
     * @return list<array{id: string, nama: string}>
     */
    public static function provinsi(): array
    {
        return self::ambil('provinces');
    }

    /**
     * @return list<array{id: string, nama: string}>
     */
    public static function kota(string $idProvinsi): array
    {
        return self::ambil("regencies/{$idProvinsi}");
    }

    /**
     * @return list<array{id: string, nama: string}>
     */
    public static function kecamatan(string $idKota): array
    {
        return self::ambil("districts/{$idKota}");
    }

    /**
     * @return list<array{id: string, nama: string}>
     */
    public static function kelurahan(string $idKecamatan): array
    {
        return self::ambil("villages/{$idKecamatan}");
    }

    /**
     * @return list<array{id: string, nama: string}>
     */
    private static function ambil(string $path): array
    {
        // Kegagalan tidak di-cache, jadi permintaan berikutnya mencoba lagi.
        return Cache::remember("wilayah:{$path}", now()->addDays(self::CACHE_HARI), fn () => collect(
            Http::timeout(10)->retry(2, 300)->get(rtrim(config('services.wilayah.url'), '/')."/{$path}.json")->throw()->json('data'),
        )->map(fn (array $baris) => [
            'id' => (string) $baris['code'],
            'nama' => (string) $baris['name'],
        ])->values()->all());
    }
}
