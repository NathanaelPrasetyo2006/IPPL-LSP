<?php

namespace Database\Factories;

use App\Enums\StatusPermohonan;
use App\Enums\TujuanAsesmen;
use App\Models\DataDiri;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PermohonanSertifikasi>
 */
class PermohonanSertifikasiFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id_data_diri' => DataDiri::factory()->lengkap(),
            'id_periode' => PeriodePendaftaran::factory(),
            'id_skema' => SkemaSertifikasi::factory(),
            'tujuan_asesmen' => TujuanAsesmen::Sertifikasi,
            'status' => StatusPermohonan::Diajukan,
        ];
    }
}
