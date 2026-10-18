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

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Menunggu verifikasi',
            self::Diterima => 'Diterima',
            self::Ditolak => 'Ditolak',
        };
    }

    /**
     * Permohonan yang masih "memakai" kuota dan menghalangi pendaftaran ulang ke skema yang sama.
     *
     * @return list<string>
     */
    public static function aktif(): array
    {
        return [self::Diajukan->value, self::Diterima->value];
    }
}
