<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(fn () => Cache::flush());

test('regions are fetched from the API and cached', function () {
    Http::fake([
        '*/provinces.json' => Http::response(['data' => [['code' => '35', 'name' => 'Jawa Timur']], 'meta' => []]),
        '*/regencies/35.json' => Http::response(['data' => [['code' => '35.78', 'name' => 'Kota Surabaya']], 'meta' => []]),
    ]);

    $user = User::factory()->create();

    $this->actingAs($user)->getJson('/wilayah/provinsi')->assertOk()->assertExactJson([['id' => '35', 'nama' => 'Jawa Timur']]);
    $this->actingAs($user)->getJson('/wilayah/kota/35')->assertOk()->assertExactJson([['id' => '35.78', 'nama' => 'Kota Surabaya']]);

    // Permintaan kedua dilayani dari cache.
    $this->actingAs($user)->getJson('/wilayah/provinsi')->assertOk();
    Http::assertSentCount(2);
});

test('an unreachable region API answers 503 so the form falls back to manual input', function () {
    Http::fake(['*' => Http::response(null, 500)]);

    $this->actingAs(User::factory()->create())->getJson('/wilayah/provinsi')->assertStatus(503);
});

test('region codes may only contain digits and dots, and the endpoints require login', function () {
    Http::fake();

    $this->getJson('/wilayah/provinsi')->assertUnauthorized();
    $this->actingAs(User::factory()->create())->getJson('/wilayah/kota/abc')->assertNotFound();

    Http::assertNothingSent();
});
