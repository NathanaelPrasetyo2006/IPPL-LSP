<?php

namespace Database\Factories;

use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UnitKompetensi>
 */
class UnitKompetensiFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_skema' => SkemaSertifikasi::factory(),
            'kode' => 'J.620100.'.fake()->unique()->numerify('###').'.01',
            'judul' => 'Menerapkan '.fake()->words(3, true),
            'urutan' => fake()->numberBetween(1, 20),
        ];
    }
}
