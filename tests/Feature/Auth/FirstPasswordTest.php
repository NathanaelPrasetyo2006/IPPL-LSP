<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->asesor = User::factory()->asesor()->create(['must_change_password' => true]);
});

test('login with a temporary password lands on the change password page', function () {
    $this->post('/login/asesor', ['email' => $this->asesor->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));

    $this->get('/dashboard')->assertRedirect(route('password.first-change'));
    $this->get('/first-password')->assertOk();
});

test('every menu stays locked until the temporary password is changed', function (string $method, string $url) {
    $this->actingAs($this->asesor)->{$method}($url)->assertRedirect(route('password.first-change'));
})->with([
    ['get', '/dashboard'],
    ['get', '/asesor/biodata'],
    ['get', '/asesor/kartu'],
    ['get', '/master/skema-sertifikasi'],
    ['get', '/master/tuk'],
    ['get', '/settings/profile'],
    ['get', '/settings/password'],
    ['put', '/asesor/biodata'],
    ['post', '/notifikasi/baca-semua'],
]);

test('the new password must differ from the temporary one and be confirmed', function () {
    $this->actingAs($this->asesor)
        ->post('/first-password', ['current_password' => 'salah', 'password' => 'password-baru', 'password_confirmation' => 'password-baru'])
        ->assertSessionHasErrors('current_password');

    $this->actingAs($this->asesor)
        ->post('/first-password', ['current_password' => 'password', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('password');

    $this->actingAs($this->asesor)
        ->post('/first-password', ['current_password' => 'password', 'password' => 'password-baru', 'password_confirmation' => 'lain'])
        ->assertSessionHasErrors('password');

    expect($this->asesor->fresh()->must_change_password)->toBeTrue();
});

test('changing the temporary password unlocks the menus', function () {
    $this->actingAs($this->asesor)
        ->post('/first-password', ['current_password' => 'password', 'password' => 'password-baru', 'password_confirmation' => 'password-baru'])
        ->assertRedirect(route('dashboard'));

    $this->asesor->refresh();

    expect($this->asesor->must_change_password)->toBeFalse()
        ->and(Hash::check('password-baru', $this->asesor->password))->toBeTrue();

    $this->actingAs($this->asesor)->get('/dashboard')->assertOk();

    // Halaman login perdana tidak dibutuhkan lagi, tetapi menu ganti password di pengaturan tetap bisa dipakai.
    $this->actingAs($this->asesor)->get('/first-password')->assertRedirect(route('dashboard'));
    $this->actingAs($this->asesor)->get('/settings/password')->assertOk();

    $this->actingAs($this->asesor)
        ->put('/settings/password', ['current_password' => 'password-baru', 'password' => 'password-ketiga', 'password_confirmation' => 'password-ketiga'])
        ->assertSessionHasNoErrors();

    expect(Hash::check('password-ketiga', $this->asesor->fresh()->password))->toBeTrue();
});
