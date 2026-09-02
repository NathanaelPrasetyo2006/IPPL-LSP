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
        $kota = fake()->randomElement(['Jakarta', 'Bandung', 'Surabaya', 'Medan', 'Makassar', 'Yogyakarta', 'Denpasar']);

        return [
            'kode' => 'TUK-'.fake()->unique()->numerify('###'),
            'nama' => 'TUK '.fake()->randomElement(['Mandiri', 'Sewaktu', 'Tempat Kerja']).' '.$kota,
            'jenis' => fake()->randomElement(array_keys(Tuk::JENIS)),
            'alamat' => fake()->streetAddress(),
            'kota' => $kota,
            'penanggung_jawab' => fake()->name(),
            'telepon' => fake()->numerify('08##########'),
            'status' => fake()->randomElement(array_keys(Tuk::STATUS)),
        ];
    }
}
