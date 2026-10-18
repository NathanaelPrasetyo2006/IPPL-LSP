<?php

namespace App\Enums;

/**
 * Pilihan "Tujuan Asesmen" pada Bagian 2 FR-APL-01.
 */
enum TujuanAsesmen: string
{
    case Sertifikasi = 'sertifikasi';
    case SertifikasiUlang = 'sertifikasi_ulang';
    case Pkt = 'pkt';
    case Rpl = 'rpl';
    case Lainnya = 'lainnya';

    public function label(): string
    {
        return match ($this) {
            self::Sertifikasi => 'Sertifikasi',
            self::SertifikasiUlang => 'Sertifikasi Ulang',
            self::Pkt => 'Pengakuan Kompetensi Terkini (PKT)',
            self::Rpl => 'Rekognisi Pembelajaran Lampau (RPL)',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $tujuan) => [$tujuan->value => $tujuan->label()])->all();
    }
}
