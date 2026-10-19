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

test('admin can create a tuk with only name and address', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/master/tuk', ['nama' => '  TUK Mandiri Jakarta ', 'alamat' => "Jl. Merdeka No. 1\nJakarta Pusat"])
        ->assertRedirect('/master/tuk')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('tuk', ['nama' => 'TUK Mandiri Jakarta', 'alamat' => "Jl. Merdeka No. 1\nJakarta Pusat"]);
});

test('tuk name and address are required', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post('/master/tuk', ['nama' => '', 'alamat' => ''])
        ->assertSessionHasErrors(['nama', 'alamat']);
});

test('tuk name must be unique among active tuk', function () {
    $admin = User::factory()->admin()->create();
    $tuk = Tuk::factory()->create(['nama' => 'TUK Bandung']);

    $this->actingAs($admin)->post('/master/tuk', ['nama' => 'TUK Bandung', 'alamat' => 'Jl. Asia Afrika'])->assertSessionHasErrors('nama');

    // Menyimpan ulang tanpa mengganti nama tidak dianggap duplikat.
    $this->actingAs($admin)->put("/master/tuk/{$tuk->ulid}", ['nama' => 'TUK Bandung', 'alamat' => 'Alamat baru'])->assertSessionHasNoErrors();
    expect($tuk->fresh()->alamat)->toBe('Alamat baru');

    // Nama milik TUK yang sudah dihapus boleh dipakai lagi.
    $tuk->delete();
    $this->actingAs($admin)->post('/master/tuk', ['nama' => 'TUK Bandung', 'alamat' => 'Jl. Asia Afrika'])->assertSessionHasNoErrors();
});

test('tuk list can be searched by name or address', function () {
    Tuk::factory()->create(['nama' => 'TUK Surabaya', 'alamat' => 'Jl. Pemuda']);
    Tuk::factory()->create(['nama' => 'TUK Medan', 'alamat' => 'Jl. Gatot Subroto']);

    $this->actingAs(User::factory()->admin()->create())
        ->get('/master/tuk?search=pemuda')
        ->assertInertia(fn ($page) => $page->component('master/tuk/index')->has('tuk.data', 1)->where('tuk.data.0.nama', 'TUK Surabaya'));
});
