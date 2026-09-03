<?php

namespace Database\Factories;

use App\Models\Asesor;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asesor>
 */
class AsesorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'nama' => fake()->name(),
            'no_reg' => 'MET.'.fake()->unique()->numerify('###').'.'.fake()->numberBetween(2018, 2026),
        ];
    }
}
