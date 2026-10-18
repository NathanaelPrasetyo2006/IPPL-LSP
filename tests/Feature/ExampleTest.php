<?php

use App\Models\User;

it('sends guests from the root url to the login portal', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('sends logged in users from the root url to the dashboard', function () {
    $this->actingAs(User::factory()->create())
        ->get('/')
        ->assertRedirect(route('dashboard'));
});
