<?php

namespace Database\Factories;

use App\Models\PeriodePendaftaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PeriodePendaftaran>
 */
class PeriodePendaftaranFactory extends Factory
{
    /**
     * Periode yang sedang dibuka hari ini.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nama' => 'Gelombang '.fake()->unique()->numberBetween(1, 999).' '.now()->year,
            'tanggal_buka' => today()->subDays(3),
            'tanggal_tutup' => today()->addDays(14),
            'kuota' => null,
            'aktif' => true,
        ];
    }

    public function ditutup(): static
    {
        return $this->state(fn () => ['aktif' => false]);
    }

    public function dijadwalkan(): static
    {
        return $this->state(fn () => [
            'tanggal_buka' => today()->addDays(7),
            'tanggal_tutup' => today()->addDays(21),
        ]);
    }
}
