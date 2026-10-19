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
            'nama' => 'TUK '.$kota.' '.fake()->unique()->numerify('###'),
            'alamat' => fake()->streetAddress().', '.$kota,
        ];
    }
}
