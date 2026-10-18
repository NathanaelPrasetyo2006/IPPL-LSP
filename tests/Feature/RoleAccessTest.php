<?php

use App\Enums\UserRole;
use App\Models\User;

test('admin can open every master data page', function (string $url) {
    $this->actingAs(User::factory()->admin()->create())
        ->get($url)
        ->assertOk();
})->with(['/master/skema-sertifikasi', '/master/tuk', '/master/asesor']);

test('asesor can only view skema and tuk', function () {
    $asesor = User::factory()->asesor()->create();

    $this->actingAs($asesor)->get('/master/skema-sertifikasi')->assertOk();
    $this->actingAs($asesor)->get('/master/tuk')->assertOk();
    $this->actingAs($asesor)->get('/master/asesor')->assertForbidden();
});

test('asesi can not open master data pages', function (string $url) {
    $this->actingAs(User::factory()->create(['role' => UserRole::Asesi]))
        ->get($url)
        ->assertForbidden();
})->with(['/master/skema-sertifikasi', '/master/tuk', '/master/asesor']);
