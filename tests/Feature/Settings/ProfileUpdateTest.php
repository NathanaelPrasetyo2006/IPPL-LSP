<?php

use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

/**
 * Kode OTP terakhir yang dikirim ke alamat email tertentu (bukan ke akun).
 */
function kodeKeEmail(string $email): string
{
    $kode = null;

    Notification::assertSentOnDemand(KodeOtpNotification::class, function (KodeOtpNotification $notifikasi, array $channels, AnonymousNotifiable $tujuan) use ($email, &$kode) {
        if ($tujuan->routes['mail'] !== $email) {
            return false;
        }

        $kode = $notifikasi->kode;

        return true;
    });

    return $kode;
}

test('profile page is displayed', function () {
    $this->actingAs(User::factory()->create())->get('/settings/profile')->assertOk();
});

test('a new email only replaces the old one after its OTP code is entered', function () {
    $user = User::factory()->create(['email' => 'lama@example.com']);

    $this->actingAs($user)
        ->patch('/settings/profile', ['email' => 'Baru@Example.com'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/settings/profile');

    $user->refresh();

    expect($user->email)->toBe('lama@example.com')
        ->and($user->email_baru)->toBe('baru@example.com')
        ->and($user->email_verified_at)->not->toBeNull();

    $this->actingAs($user)->post('/settings/profile/email/verifikasi', ['kode' => kodeKeEmail('baru@example.com')])->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->email)->toBe('baru@example.com')
        ->and($user->email_baru)->toBeNull();
});

test('a pending email change can be cancelled', function () {
    $user = User::factory()->create(['email' => 'lama@example.com']);

    $this->actingAs($user)->patch('/settings/profile', ['email' => 'baru@example.com']);
    $kode = kodeKeEmail('baru@example.com');

    $this->actingAs($user)->delete('/settings/profile/email');
    $this->actingAs($user)->post('/settings/profile/email/verifikasi', ['kode' => $kode]);

    expect($user->fresh())->email->toBe('lama@example.com')->email_baru->toBeNull();
});

test('the new email must not belong to another account', function () {
    $user = User::factory()->create();
    $lain = User::factory()->create();

    $this->actingAs($user)->patch('/settings/profile', ['email' => $lain->email])->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

test('asesi can deactivate their account, which keeps the data but clears verification', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->delete('/settings/profile', ['password' => 'password'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login.role', 'asesi'));

    $this->assertGuest();

    $user->refresh();

    expect($user->isNonaktif())->toBeTrue()
        ->and($user->email_verified_at)->toBeNull();
});

test('correct password must be provided to deactivate the account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/profile')
        ->delete('/settings/profile', ['password' => 'wrong-password'])
        ->assertSessionHasErrors('password');

    expect($user->fresh()->isNonaktif())->toBeFalse();
});

test('admin and asesor cannot deactivate their account', function () {
    foreach ([User::factory()->admin()->create(), User::factory()->asesor()->create()] as $user) {
        $this->actingAs($user)->delete('/settings/profile', ['password' => 'password'])->assertForbidden();

        expect($user->fresh()->isNonaktif())->toBeFalse();
    }
});
