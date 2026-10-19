<?php

namespace App\Enums;

/**
 * Status formulir FR-APL-01 sejak diajukan calon asesi sampai diputuskan verifikator.
 */
enum StatusPermohonan: string
{
    case Diajukan = 'diajukan';
    case Diterima = 'diterima';
    case Ditolak = 'ditolak';
    // Dibatalkan sendiri oleh asesi sebelum diverifikasi, mis. karena salah memilih skema.
    case Dibatalkan = 'dibatalkan';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu verifikasi',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Permohonan yang masih "memakai" kuota. Asesi hanya boleh punya satu permohonan aktif per periode.
     *
     * @return list<string>
     */
    public static function aktif(): array
    {
        return [self::Diajukan->value, self::Diterima->value];
    }
}
