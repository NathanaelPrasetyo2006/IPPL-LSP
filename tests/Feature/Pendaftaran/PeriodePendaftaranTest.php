<?php

use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\Tuk;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->skema = SkemaSertifikasi::factory()->count(2)->create();
    $this->tuk = Tuk::factory()->count(2)->create();
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

test('admin can open a registration period with selected schemes and locations', function () {
    [$skemaA, $skemaB] = $this->skema->pluck('id_skema')->all();
    [$tukA, $tukB] = $this->tuk->pluck('id_tuk')->all();

    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode([
            'skema' => [$skemaA, $skemaB],
            'lokasi' => [
                ['id_tuk' => (string) $tukA, 'skema' => [$skemaA, $skemaB]],
                ['id_tuk' => (string) $tukB, 'skema' => [$skemaB]],
            ],
        ]))
        ->assertRedirect(route('pendaftaran.periode.index'));

    $periode = PeriodePendaftaran::firstWhere('nama', 'Gelombang 1 2026');

    expect($periode->skema)->toHaveCount(2)
        ->and($periode->status())->toBe('dibuka')
        ->and(collect($periode->lokasiForm())->sortBy('id_tuk')->values()->all())->toEqualCanonicalizing([
            ['id_tuk' => (string) $tukA, 'skema' => [$skemaA, $skemaB]],
            ['id_tuk' => (string) $tukB, 'skema' => [$skemaB]],
        ]);

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

test('every period needs at least one location and every location needs schemes from the period', function () {
    [$skemaA, $skemaB] = $this->skema->pluck('id_skema')->all();
    [$tukA] = $this->tuk->pluck('id_tuk')->all();

    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode(['skema' => [$skemaA], 'lokasi' => []]))
        ->assertSessionHasErrors('lokasi');

    // Lokasi tanpa skema, TUK belum dipilih, dan TUK ganda.
    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode([
            'skema' => [$skemaA],
            'lokasi' => [
                ['id_tuk' => (string) $tukA, 'skema' => []],
                ['id_tuk' => '', 'skema' => [$skemaA]],
                ['id_tuk' => (string) $tukA, 'skema' => [$skemaA]],
            ],
        ]))
        ->assertSessionHasErrors(['lokasi.0.skema', 'lokasi.1.id_tuk', 'lokasi.2.id_tuk']);

    // Skema lokasi harus termasuk skema yang dibuka di atas.
    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode([
            'skema' => [$skemaA],
            'lokasi' => [['id_tuk' => (string) $tukA, 'skema' => [$skemaB]]],
        ]))
        ->assertSessionHasErrors('lokasi.0.skema.0');

    // Setiap skema yang dibuka wajib punya lokasi.
    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode([
            'skema' => [$skemaA, $skemaB],
            'lokasi' => [['id_tuk' => (string) $tukA, 'skema' => [$skemaA]]],
        ]))
        ->assertSessionHasErrors('lokasi');

    expect(PeriodePendaftaran::count())->toBe(0);
});

test('editing a period replaces its locations', function () {
    [$skemaA, $skemaB] = $this->skema->pluck('id_skema')->all();
    [$tukA, $tukB] = $this->tuk->pluck('id_tuk')->all();

    $periode = PeriodePendaftaran::factory()->create();
    $periode->skema()->sync([$skemaA, $skemaB]);
    $periode->simpanLokasi([['id_tuk' => $tukA, 'skema' => [$skemaA, $skemaB]]]);

    $this->actingAs($this->admin)
        ->get("/pendaftaran/periode/{$periode->id_periode}/edit")
        ->assertInertia(fn ($page) => $page
            ->where('periode.lokasi.0.id_tuk', (string) $tukA)
            ->has('periode.lokasi.0.skema', 2)
            ->has('tukOptions', 2));

    $this->actingAs($this->admin)
        ->put("/pendaftaran/periode/{$periode->id_periode}", dataPeriode([
            'skema' => [$skemaB],
            'lokasi' => [['id_tuk' => (string) $tukB, 'skema' => [$skemaB]]],
        ]))
        ->assertSessionHasNoErrors();

    expect($periode->fresh()->lokasiForm())->toBe([['id_tuk' => (string) $tukB, 'skema' => [$skemaB]]]);
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
        ->put("/pendaftaran/periode/{$periode->id_periode}", dataPeriode([
            'skema' => [$this->skema[0]->id_skema],
            'lokasi' => [['id_tuk' => (string) $this->tuk[0]->id_tuk, 'skema' => [$this->skema[0]->id_skema]]],
        ]))
        ->assertSessionHasErrors('skema');
});

test('only admin can manage periods', function () {
    foreach ([User::factory()->asesor()->create(), User::factory()->create()] as $user) {
        $this->actingAs($user)->get('/pendaftaran/periode')->assertForbidden();
        $this->actingAs($user)->post('/pendaftaran/periode', dataPeriode())->assertForbidden();
    }
});

test('quota is required and at most 10.000', function () {
    $lokasi = [['id_tuk' => (string) $this->tuk[0]->id_tuk, 'skema' => [$this->skema[0]->id_skema]]];

    foreach ([null, 0, 10001] as $kuota) {
        $this->actingAs($this->admin)
            ->post('/pendaftaran/periode', dataPeriode(['kuota' => $kuota, 'skema' => [$this->skema[0]->id_skema], 'lokasi' => $lokasi]))
            ->assertSessionHasErrors('kuota');
    }

    $this->actingAs($this->admin)
        ->post('/pendaftaran/periode', dataPeriode(['kuota' => 10000, 'skema' => [$this->skema[0]->id_skema], 'lokasi' => $lokasi]))
        ->assertSessionHasNoErrors();
});

test('period detail lists applicants and can filter them by scheme and search', function () {
    [$skemaA, $skemaB] = $this->skema;
    $periode = PeriodePendaftaran::factory()->create(['kuota' => 10]);
    $periode->skema()->sync([$skemaA->id_skema, $skemaB->id_skema]);

    $budi = PermohonanSertifikasi::factory()->create(['id_periode' => $periode->id_periode, 'id_skema' => $skemaA->id_skema]);
    $budi->dataDiri->update(['nama_lengkap' => 'Budi Santoso', 'no_telp' => '081111111111']);
    $sari = PermohonanSertifikasi::factory()->create(['id_periode' => $periode->id_periode, 'id_skema' => $skemaB->id_skema]);
    $sari->dataDiri->update(['nama_lengkap' => 'Sari Dewi']);
    PermohonanSertifikasi::factory()->create(); // periode lain

    $url = "/pendaftaran/periode/{$periode->id_periode}";

    $this->actingAs($this->admin)
        ->get($url)
        ->assertInertia(fn ($page) => $page
            ->component('pendaftaran/periode/show')
            ->has('pendaftar.data', 2)
            ->where('periode.kuota_terpakai', 2)
            ->has('pendaftar.data.0', fn ($row) => $row
                ->hasAll(['kode_asesi', 'nama', 'email', 'no_telp', 'skema', 'lokasi', 'status'])
                ->etc()));

    $this->actingAs($this->admin)
        ->get("{$url}?skema={$skemaB->id_skema}")
        ->assertInertia(fn ($page) => $page->has('pendaftar.data', 1)->where('pendaftar.data.0.nama', 'Sari Dewi'));

    // Pencarian berlaku untuk nama, kode asesi, email, dan no. HP.
    foreach (['budi', $budi->dataDiri->kode_asesi, '081111111111', $budi->dataDiri->user->email] as $kataKunci) {
        $this->actingAs($this->admin)
            ->get($url.'?search='.urlencode($kataKunci))
            ->assertInertia(fn ($page) => $page->has('pendaftar.data', 1)->where('pendaftar.data.0.nama', 'Budi Santoso'));
    }
});

test('only admin can open the period detail', function () {
    $periode = PeriodePendaftaran::factory()->create();

    $this->actingAs(User::factory()->asesor()->create())->get("/pendaftaran/periode/{$periode->id_periode}")->assertForbidden();
    $this->actingAs(User::factory()->create())->get("/pendaftaran/periode/{$periode->id_periode}")->assertForbidden();
});
