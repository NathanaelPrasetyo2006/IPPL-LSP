<?php

use App\Models\Tuk;
use App\Models\User;

test('asesor can view tuk but can not change it', function () {
    $asesor = User::factory()->asesor()->create();
    $tuk = Tuk::factory()->create();

    $this->actingAs($asesor)->get('/master/tuk')->assertOk();
    $this->actingAs($asesor)->get("/master/tuk/{$tuk->ulid}/edit")->assertForbidden();
    $this->actingAs($asesor)->delete("/master/tuk/{$tuk->ulid}")->assertForbidden();
});

test('deleting a tuk is a soft delete', function () {
    $tuk = Tuk::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->delete("/master/tuk/{$tuk->ulid}")->assertSessionHas('success');

    $this->assertSoftDeleted($tuk);
});

/**
 * Isian form TUK yang valid; wilayah berasal dari dropdown API wilayah.
 */
function tukValid(array $override = []): array
{
    return [
        'nama' => 'TUK Bandung',
        'alamat' => 'Jl. Asia Afrika No. 1',
        'provinsi' => 'Jawa Barat',
        'kota' => 'Kota Bandung',
        'kecamatan' => 'Sumur Bandung',
        'kelurahan' => 'Braga',
        ...$override,
    ];
}

test('admin can create a tuk with name, street address and region', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/master/tuk', tukValid(['nama' => '  TUK Mandiri Jakarta ', 'alamat' => "Jl. Merdeka No. 1\nGedung A"]))
        ->assertRedirect('/master/tuk')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tuk', ['nama' => 'TUK Mandiri Jakarta', 'alamat' => "Jl. Merdeka No. 1\nGedung A", 'kota' => 'Kota Bandung']);
});

test('tuk name, address and region are required', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/master/tuk', ['nama' => '', 'alamat' => ''])
        ->assertSessionHasErrors(['nama', 'alamat', 'provinsi', 'kota', 'kecamatan', 'kelurahan']);
});

test('tuk list shows the full address including its region', function () {
    Tuk::factory()->create(tukValid());

    $this->actingAs(User::factory()->admin()->create())
        ->get('/master/tuk')
        ->assertInertia(fn ($page) => $page->where('tuk.data.0.alamat', 'Jl. Asia Afrika No. 1, Braga, Sumur Bandung, Kota Bandung, Jawa Barat'));
});

test('tuk name must be unique among active tuk', function () {
    $admin = User::factory()->admin()->create();
    $tuk = Tuk::factory()->create(['nama' => 'TUK Bandung']);

    $this->actingAs($admin)->post('/master/tuk', tukValid())->assertSessionHasErrors('nama');

    // Menyimpan ulang tanpa mengganti nama tidak dianggap duplikat.
    $this->actingAs($admin)->put("/master/tuk/{$tuk->ulid}", tukValid(['alamat' => 'Alamat baru']))->assertSessionHasNoErrors();
    expect($tuk->fresh()->alamat)->toBe('Alamat baru');

    // Nama milik TUK yang sudah dihapus boleh dipakai lagi.
    $tuk->delete();
    $this->actingAs($admin)->post('/master/tuk', tukValid())->assertSessionHasNoErrors();
});

test('tuk list can be searched by name, address or region', function () {
    Tuk::factory()->create(['nama' => 'TUK Surabaya', 'alamat' => 'Jl. Pemuda', 'kota' => 'Kota Surabaya']);
    Tuk::factory()->create(['nama' => 'TUK Medan', 'alamat' => 'Jl. Gatot Subroto', 'kota' => 'Kota Medan']);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/master/tuk?search=pemuda')
        ->assertInertia(fn ($page) => $page->component('master/tuk/index')->has('tuk.data', 1)->where('tuk.data.0.nama', 'TUK Surabaya'));

    $this->actingAs($admin)
        ->get('/master/tuk?search=kota medan')
        ->assertInertia(fn ($page) => $page->has('tuk.data', 1)->where('tuk.data.0.nama', 'TUK Medan'));
});
