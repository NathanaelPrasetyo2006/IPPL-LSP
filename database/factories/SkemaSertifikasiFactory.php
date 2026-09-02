<?php

namespace Database\Factories;

use App\Models\SkemaSertifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SkemaSertifikasi>
 */
class SkemaSertifikasiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'judul' => 'Skema '.fake()->unique()->jobTitle(),
            'nomor' => sprintf(
                'SKM/%s/%s/%d',
                fake()->unique()->numerify('###'),
                fake()->randomElement(['LSP', 'BNSP', 'TIK']),
                fake()->numberBetween(2020, 2026),
            ),
        ];
    }
}
