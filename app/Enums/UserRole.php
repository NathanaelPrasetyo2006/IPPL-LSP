<?php

namespace App\Enums;

/**
 * Peran pemilik akun. Menentukan portal login yang dipakai dan menu yang boleh diakses.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Asesor = 'asesor';
    case Asesi = 'asesi';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin LSP',
            self::Asesor => 'Asesor',
            self::Asesi => 'Asesi',
        };
    }
}
