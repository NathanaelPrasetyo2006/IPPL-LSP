<?php

namespace Database\Factories;

use App\Models\DataDiri;
use App\Models\Negara;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DataDiri>
 */
class DataDiriFactory extends Factory
{
    /**
     * Profil awal hasil registrasi: hanya nama dan no. telepon.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_user' => User::factory(),
            'nama_lengkap' => fake()->name(),
            'no_telp' => '08'.fake()->numerify('##########'),
        ];
    }

    /**
     * Profil yang sudah dilengkapi lewat APL-01 (tanpa file dokumen).
     */
    public function lengkap(): static
    {
        return $this->state(fn () => [
            'nik' => fake()->unique()->numerify('35##############'),
            'tempat_lahir' => fake()->city(),
            'tgl_lahir' => fake()->dateTimeBetween('-40 years', '-20 years')->format('Y-m-d'),
            'jenis_kelamin' => fake()->randomElement(['L', 'P']),
            'id_negara' => fn () => Negara::firstOrCreate(['nama_negara' => 'Indonesia'])->id_negara,
            'alamat_ktp' => fake()->streetAddress(),
            'provinsi_ktp' => 'Jawa Timur',
            'kota_ktp' => 'Kota Surabaya',
            'kecamatan_ktp' => 'Genteng',
            'kelurahan_ktp' => 'Embong Kaliasin',
            'alamat_domisili' => fake()->streetAddress(),
            'provinsi' => 'Jawa Timur',
            'kota' => 'Kota Surabaya',
            'kecamatan' => 'Genteng',
            'kelurahan' => 'Embong Kaliasin',
            'kode_pos' => '60271',
            'jenjang_pendidikan' => 'S1',
            'nama_sekolah' => 'Universitas Contoh',
            'pekerjaan' => 'Karyawan swasta',
        ]);
    }
}
