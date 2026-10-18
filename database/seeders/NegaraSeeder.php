<?php

namespace Database\Seeders;

use App\Models\Negara;
use Illuminate\Database\Seeder;

/**
 * Pilihan kebangsaan pada FR-APL-01. Aman dijalankan berulang kali.
 */
class NegaraSeeder extends Seeder
{
    private const NEGARA = [
        'Indonesia',
        'Malaysia',
        'Singapura',
        'Brunei Darussalam',
        'Thailand',
        'Filipina',
        'Vietnam',
        'Kamboja',
        'Laos',
        'Myanmar',
        'Timor Leste',
        'Australia',
        'Selandia Baru',
        'Jepang',
        'Korea Selatan',
        'Tiongkok',
        'Taiwan',
        'Hong Kong',
        'India',
        'Pakistan',
        'Bangladesh',
        'Arab Saudi',
        'Uni Emirat Arab',
        'Qatar',
        'Mesir',
        'Turki',
        'Belanda',
        'Jerman',
        'Prancis',
        'Inggris',
        'Amerika Serikat',
        'Kanada',
    ];

    public function run(): void
    {
        foreach (self::NEGARA as $nama) {
            Negara::firstOrCreate(['nama_negara' => $nama]);
        }
    }
}
