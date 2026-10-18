<?php

use App\Models\User;
use App\Notifications\ResetPasswordNotification as ResetPassword;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered for each role', function (string $role) {
    $this->get("/forgot-password/{$role}")->assertStatus(200);
})->with(['admin', 'asesor', 'asesi']);

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password link is not sent through the portal of another role', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password/admin', ['email' => $user->email])->assertSessionHas('status');

    Notification::assertNothingSent();
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('password can be reset with valid token', function () {
    Notification::fake();

    $user = User::factory()->asesor()->create(['must_change_password' => true]);

    $this->post('/forgot-password/asesor', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post('/reset-password', [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login.role', 'asesor'));

        expect($user->fresh()->must_change_password)->toBeFalse();

        return true;
    });
});
