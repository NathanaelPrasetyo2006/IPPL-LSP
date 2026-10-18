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
