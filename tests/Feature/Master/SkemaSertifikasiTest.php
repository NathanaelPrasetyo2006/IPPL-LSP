<?php

use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

test('index includes the units of each skema for the detail popup', function () {
    $skema = SkemaSertifikasi::factory()->create();
    UnitKompetensi::factory()->count(2)->create(['id_skema' => $skema->id_skema]);

    $this->actingAs($this->admin)
        ->get('/master/skema-sertifikasi')
        ->assertInertia(fn ($page) => $page
            ->has('skema.data.0.unit_kompetensi', 2)
            ->where('skema.data.0.unit_kompetensi_count', 2));
});

test('asesor can view skema but can not change it', function () {
    $asesor = User::factory()->asesor()->create();
    $skema = SkemaSertifikasi::factory()->create();

    $this->actingAs($asesor)->get('/master/skema-sertifikasi')->assertOk();
    $this->actingAs($asesor)->get('/master/skema-sertifikasi/create')->assertForbidden();
    $this->actingAs($asesor)->delete("/master/skema-sertifikasi/{$skema->ulid}")->assertForbidden();
    $this->actingAs($asesor)
        ->post("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi", ['kode' => 'X', 'judul' => 'X'])
        ->assertForbidden();
    $this->actingAs($asesor)
        ->put("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi/susun", ['urutan' => [], 'hapus' => []])
        ->assertForbidden();
});

test('new units are appended to the end and updating keeps their order', function () {
    $skema = SkemaSertifikasi::factory()->create();
    UnitKompetensi::factory()->create(['id_skema' => $skema->id_skema, 'urutan' => 4]);

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi", [
            'kode' => 'J.620100.001.01',
            'judul' => 'Menganalisis Tools',
            'urutan' => 1, // Diabaikan: urutan selalu ditentukan sistem.
        ])
        ->assertSessionHasNoErrors();

    $unit = $skema->unitKompetensi()->where('kode', 'J.620100.001.01')->sole();
    expect($unit->urutan)->toBe(5);

    $this->actingAs($this->admin)
        ->put("/master/unit-kompetensi/{$unit->ulid}", ['kode' => $unit->kode, 'judul' => 'Judul Baru'])
        ->assertSessionHasNoErrors();

    expect($unit->fresh())->judul->toBe('Judul Baru')->urutan->toBe(5);
});

test('saving the arranged list renumbers units and soft deletes removed ones', function () {
    $skema = SkemaSertifikasi::factory()->create();
    [$satu, $dua, $tiga, $empat] = collect([1, 2, 3, 4])
        ->map(fn ($urutan) => UnitKompetensi::factory()->create(['id_skema' => $skema->id_skema, 'urutan' => $urutan]))
        ->all();

    // Unit nomor 1 dihapus, unit nomor 4 diseret ke paling atas.
    $this->actingAs($this->admin)
        ->put("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi/susun", [
            'urutan' => [$empat->id_unit_kompetensi, $dua->id_unit_kompetensi, $tiga->id_unit_kompetensi],
            'hapus' => [$satu->id_unit_kompetensi],
        ])
        ->assertSessionHas('success');

    $this->assertSoftDeleted($satu);

    expect($skema->unitKompetensi()->orderBy('urutan')->pluck('urutan', 'id_unit_kompetensi')->all())->toBe([
        $empat->id_unit_kompetensi => 1,
        $dua->id_unit_kompetensi => 2,
        $tiga->id_unit_kompetensi => 3,
    ]);
});

test('arranging is rejected when the list on screen is stale or belongs to another skema', function () {
    $skema = SkemaSertifikasi::factory()->create();
    $unit = UnitKompetensi::factory()->create(['id_skema' => $skema->id_skema, 'urutan' => 1]);
    $baru = UnitKompetensi::factory()->create(['id_skema' => $skema->id_skema, 'urutan' => 2]);
    $milikLain = UnitKompetensi::factory()->create();

    $url = "/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi/susun";

    // Unit $baru tidak ikut terkirim, mis. ditambahkan admin lain setelah halaman dibuka.
    $this->actingAs($this->admin)->put($url, ['urutan' => [$unit->id_unit_kompetensi], 'hapus' => []])->assertSessionHas('error');

    $this->actingAs($this->admin)
        ->put($url, ['urutan' => [$unit->id_unit_kompetensi, $baru->id_unit_kompetensi], 'hapus' => [$milikLain->id_unit_kompetensi]])
        ->assertSessionHas('error');

    expect($milikLain->fresh()->trashed())->toBeFalse()
        ->and($baru->fresh()->urutan)->toBe(2);
});

test('unit code must be unique within a skema but may repeat across skema', function () {
    $unit = UnitKompetensi::factory()->create();
    $skemaLain = SkemaSertifikasi::factory()->create();

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$unit->skema->ulid}/unit-kompetensi", ['kode' => $unit->kode, 'judul' => 'Duplikat'])
        ->assertSessionHasErrors('kode');

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skemaLain->ulid}/unit-kompetensi", ['kode' => $unit->kode, 'judul' => 'Skema lain'])
        ->assertSessionHasNoErrors();
});

test('deleting a skema is a soft delete and frees its code for reuse', function () {
    $skema = SkemaSertifikasi::factory()->create(['nomor' => 'SKM/001']);

    $this->actingAs($this->admin)->delete("/master/skema-sertifikasi/{$skema->ulid}")->assertSessionHas('success');

    $this->assertSoftDeleted($skema);

    $this->actingAs($this->admin)
        ->post('/master/skema-sertifikasi', ['nomor' => 'SKM/001', 'judul' => 'Skema pengganti'])
        ->assertSessionHasNoErrors();

    expect(SkemaSertifikasi::where('nomor', 'SKM/001')->count())->toBe(1)
        ->and(SkemaSertifikasi::withTrashed()->where('nomor', 'SKM/001')->count())->toBe(2);
});

test('skema and unit codes may not contain spaces', function () {
    $skema = SkemaSertifikasi::factory()->create();

    $this->actingAs($this->admin)
        ->post('/master/skema-sertifikasi', ['nomor' => 'SKM 002', 'judul' => 'Skema berspasi'])
        ->assertSessionHasErrors(['nomor' => 'Kode skema tidak boleh mengandung spasi.']);

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi", ['kode' => 'J.620100 .001.01', 'judul' => 'Unit berspasi'])
        ->assertSessionHasErrors(['kode' => 'Kode unit tidak boleh mengandung spasi.']);
});

test('skema and unit codes are stored in capitals so letter case can not bypass uniqueness', function () {
    $this->actingAs($this->admin)
        ->post('/master/skema-sertifikasi', ['nomor' => ' ss-kkni-001 ', 'judul' => 'Skema huruf kecil'])
        ->assertSessionHasNoErrors();

    $skema = SkemaSertifikasi::sole();
    expect($skema->nomor)->toBe('SS-KKNI-001');

    $this->actingAs($this->admin)
        ->post('/master/skema-sertifikasi', ['nomor' => 'Ss-Kkni-001', 'judul' => 'Duplikat'])
        ->assertSessionHasErrors('nomor');

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi", ['kode' => 'j.620100.001.01', 'judul' => 'Unit'])
        ->assertSessionHasNoErrors();

    expect($skema->unitKompetensi()->sole()->kode)->toBe('J.620100.001.01');

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skema->ulid}/unit-kompetensi", ['kode' => 'J.620100.001.01', 'judul' => 'Duplikat'])
        ->assertSessionHasErrors('kode');
});
