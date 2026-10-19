<?php

use App\Enums\UserRole;
use App\Models\User;

test('login portal can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('login screen can be rendered for each role', function (string $role) {
    $this->get("/login/{$role}")->assertStatus(200);
})->with(['admin', 'asesor', 'asesi']);

test('login screen for an unknown role returns 404', function () {
    $this->get('/login/superadmin')->assertNotFound();
});

test('users can authenticate through the portal of their own role', function (string $role) {
    $user = User::factory()->create(['role' => UserRole::from($role)]);

    $response = $this->post("/login/{$role}", [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
})->with(['admin', 'asesor', 'asesi']);

test('users can not authenticate through the portal of another role', function () {
    $user = User::factory()->create();

    $this->post('/login/admin', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login/asesi', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('a leftover page of another role is ignored after login instead of ending in 403', function () {
    $admin = User::factory()->admin()->create();

    // Mis. sesi asesi kedaluwarsa di /sertifikasi pada browser yang sama, lalu admin masuk.
    $this->get('/sertifikasi')->assertRedirect(route('login'));

    $this->post('/login/admin', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
});

test('the page requested before login is still opened when the role may access it', function () {
    $admin = User::factory()->admin()->create();

    $this->get('/master/tuk');

    $this->post('/login/admin', ['email' => $admin->email, 'password' => 'password'])
        ->assertRedirect(route('master.tuk.index'));
});

test('unverified accounts cannot open the dashboard', function () {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/dashboard')
        ->assertRedirect(route('verification.notice'));
});
