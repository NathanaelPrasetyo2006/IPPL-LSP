<?php

namespace Database\Factories;

use App\Models\Tuk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tuk>
 */
class TukFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$provinsi, $kota, $kecamatan, $kelurahan] = fake()->randomElement([
            ['DKI Jakarta', 'Kota Jakarta Pusat', 'Gambir', 'Gambir'],
            ['Jawa Barat', 'Kota Bandung', 'Sumur Bandung', 'Braga'],
            ['Jawa Timur', 'Kota Surabaya', 'Genteng', 'Embong Kaliasin'],
            ['Sumatera Utara', 'Kota Medan', 'Medan Petisah', 'Petisah Tengah'],
            ['Daerah Istimewa Yogyakarta', 'Kota Yogyakarta', 'Gondokusuman', 'Terban'],
        ]);

        return [
            'nama' => 'TUK '.str_replace('Kota ', '', $kota).' '.fake()->unique()->numerify('###'),
            'alamat' => fake()->streetAddress(),
            'provinsi' => $provinsi,
            'kota' => $kota,
            'kecamatan' => $kecamatan,
            'kelurahan' => $kelurahan,
        ];
    }
}
