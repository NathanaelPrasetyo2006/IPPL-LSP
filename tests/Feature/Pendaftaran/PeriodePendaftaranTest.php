<?php

use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->skema = SkemaSertifikasi::factory()->count(2)->create();
});

function dataPeriode(array $override = []): array
{
    return [
        'nama' => 'Gelombang 1 2026',
        'tanggal_buka' => today()->toDateString(),
        'tanggal_tutup' => today()->addDays(14)->toDateString(),
        'kuota' => 50,
        'keterangan' => 'Biaya asesmen Rp500.000',
        'aktif' => true,
        ...$override,
    ];
}

test('admin can open a registration period with selected schemes', function () {
    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode(['skema' => $this->skema->pluck('id_skema')->all()]))
        ->assertRedirect(route('pendaftaran.periode.index'));

    $periode = PeriodePendaftaran::firstWhere('nama', 'Gelombang 1 2026');

    expect($periode->skema)->toHaveCount(2)
        ->and($periode->status())->toBe('dibuka');

    $this->actingAs($this->admin)
        ->get('/pendaftaran/periode')
        ->assertInertia(fn ($page) => $page
            ->component('pendaftaran/periode/index')
            ->where('periode.data.0.status', 'dibuka')
            ->where('stats.periode_dibuka', 1));
});

test('period requires valid dates and at least one scheme', function () {
    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode([
            'tanggal_tutup' => today()->subDay()->toDateString(),
            'skema' => [],
        ]))
        ->assertSessionHasErrors(['tanggal_tutup', 'skema']);
});

test('period status follows dates and the active flag', function () {
    expect(PeriodePendaftaran::factory()->create()->status())->toBe('dibuka')
        ->and(PeriodePendaftaran::factory()->dijadwalkan()->create()->status())->toBe('dijadwalkan')
        ->and(PeriodePendaftaran::factory()->ditutup()->create()->status())->toBe('ditutup');
});

test('admin can close a period early and reopen it', function () {
    $periode = PeriodePendaftaran::factory()->create();

    $this->actingAs($this->admin)->patch("/pendaftaran/periode/{$periode->id_periode}/status");
    expect($periode->fresh()->status())->toBe('ditutup');

    $this->actingAs($this->admin)->patch("/pendaftaran/periode/{$periode->id_periode}/status");
    expect($periode->fresh()->status())->toBe('dibuka');
});

test('periods with applicants cannot be deleted or lose their applied schemes', function () {
    $permohonan = PermohonanSertifikasi::factory()->create();
    $periode = $permohonan->periode;
    $periode->skema()->sync([$permohonan->id_skema, $this->skema[0]->id_skema]);

    $this->actingAs($this->admin)
        ->delete("/pendaftaran/periode/{$periode->id_periode}")
        ->assertSessionHas('error');

    expect($periode->fresh())->not->toBeNull();

    $this->actingAs($this->admin)
        ->put("/pendaftaran/periode/{$periode->id_periode}", dataPeriode(['skema' => [$this->skema[0]->id_skema]]))
        ->assertSessionHasErrors('skema');
});

test('only admin can manage periods', function () {
    foreach ([User::factory()->asesor()->create(), User::factory()->create()] as $user) {
        $this->actingAs($user)->get('/pendaftaran/periode')->assertForbidden();
        $this->actingAs($user)->post('/pendaftaran/periode', dataPeriode())->assertForbidden();
    }
});
