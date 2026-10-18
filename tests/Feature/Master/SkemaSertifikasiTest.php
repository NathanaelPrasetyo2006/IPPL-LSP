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
    $this->actingAs($asesor)->delete("/master/skema-sertifikasi/{$skema->id_skema}")->assertForbidden();
    $this->actingAs($asesor)
        ->post("/master/skema-sertifikasi/{$skema->id_skema}/unit-kompetensi", ['kode' => 'X', 'judul' => 'X', 'urutan' => 1])
        ->assertForbidden();
});

test('admin can add, update, and soft delete a unit kompetensi', function () {
    $skema = SkemaSertifikasi::factory()->create();

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skema->id_skema}/unit-kompetensi", [
            'kode' => 'J.620100.001.01',
            'judul' => 'Menganalisis Tools',
            'urutan' => 1,
        ])
        ->assertSessionHasNoErrors();

    $unit = $skema->unitKompetensi()->sole();

    $this->actingAs($this->admin)
        ->put("/master/unit-kompetensi/{$unit->id_unit_kompetensi}", ['kode' => $unit->kode, 'judul' => 'Judul Baru', 'urutan' => 2])
        ->assertSessionHasNoErrors();

    expect($unit->fresh())->judul->toBe('Judul Baru')->urutan->toBe(2);

    $this->actingAs($this->admin)->delete("/master/unit-kompetensi/{$unit->id_unit_kompetensi}");

    $this->assertSoftDeleted($unit);
});

test('unit code must be unique within a skema but may repeat across skema', function () {
    $unit = UnitKompetensi::factory()->create();
    $skemaLain = SkemaSertifikasi::factory()->create();

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$unit->id_skema}/unit-kompetensi", ['kode' => $unit->kode, 'judul' => 'Duplikat', 'urutan' => 2])
        ->assertSessionHasErrors('kode');

    $this->actingAs($this->admin)
        ->post("/master/skema-sertifikasi/{$skemaLain->id_skema}/unit-kompetensi", ['kode' => $unit->kode, 'judul' => 'Skema lain', 'urutan' => 1])
        ->assertSessionHasNoErrors();
});

test('deleting a skema is a soft delete and frees its code for reuse', function () {
    $skema = SkemaSertifikasi::factory()->create(['nomor' => 'SKM/001']);

    $this->actingAs($this->admin)->delete("/master/skema-sertifikasi/{$skema->id_skema}")->assertSessionHas('success');

    $this->assertSoftDeleted($skema);

    $this->actingAs($this->admin)
        ->post('/master/skema-sertifikasi', ['nomor' => 'SKM/001', 'judul' => 'Skema pengganti'])
        ->assertSessionHasNoErrors();

    expect(SkemaSertifikasi::where('nomor', 'SKM/001')->count())->toBe(1)
        ->and(SkemaSertifikasi::withTrashed()->where('nomor', 'SKM/001')->count())->toBe(2);
});
