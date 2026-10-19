<?php

namespace App\Http\Controllers;

use App\Support\Wilayah;
use Illuminate\Http\JsonResponse;
use Throwable;

/**
 * Daftar wilayah untuk dropdown alamat di formulir APL-01.
 */
class WilayahController extends Controller
{
    public function provinsi(): JsonResponse
    {
        return $this->jawab(fn () => Wilayah::provinsi());
    }

    public function kota(string $provinsi): JsonResponse
    {
        return $this->jawab(fn () => Wilayah::kota($provinsi));
    }

    public function kecamatan(string $kota): JsonResponse
    {
        return $this->jawab(fn () => Wilayah::kecamatan($kota));
    }

    public function kelurahan(string $kecamatan): JsonResponse
    {
        return $this->jawab(fn () => Wilayah::kelurahan($kecamatan));
    }

    /**
     * Bila API wilayah tidak bisa dihubungi, formulir beralih ke isian manual.
     *
     * @param  callable(): list<array{id: string, nama: string}>  $ambil
     */
    private function jawab(callable $ambil): JsonResponse
    {
        try {
            return response()->json($ambil());
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'Data wilayah sedang tidak dapat dimuat.'], 503);
        }
    }
}
