<?php

use App\Enums\TujuanOtp;
use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

function kodeVerifikasiEmail(User $user): string
{
    $kode = null;

    Notification::assertSentTo($user, KodeOtpNotification::class, function (KodeOtpNotification $notifikasi) use (&$kode) {
        $kode = $notifikasi->kode;

        return $notifikasi->tujuan === TujuanOtp::VerifikasiEmail;
    });

    return $kode;
}

test('an unverified asesi cannot log in and is sent to the verification code page', function () {
    $user = User::factory()->unverified()->create();

    $this->post('/login/asesi', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('verifikasi-email.show'));

    $this->assertGuest();
    kodeVerifikasiEmail($user);

    $this->get('/verifikasi-email')->assertInertia(fn ($page) => $page->component('auth/verifikasi-email'));
});

test('a wrong password on an unverified account is just a failed login', function () {
    $user = User::factory()->unverified()->create();

    $this->post('/login/asesi', ['email' => $user->email, 'password' => 'salah'])->assertSessionHasErrors('email');

    $this->get('/verifikasi-email')->assertRedirect(route('login.role', 'asesi'));
    Notification::assertNothingSent();
});

test('the correct code verifies the email and logs the asesi in', function () {
    Event::fake([Verified::class]);
    $user = User::factory()->unverified()->create();

    $this->post('/login/asesi', ['email' => $user->email, 'password' => 'password']);

    $this->post('/verifikasi-email', ['kode' => '000000'])->assertSessionHasErrors('kode');
    $this->assertGuest();

    $this->post('/verifikasi-email', ['kode' => kodeVerifikasiEmail($user)])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

test('verification status is read from the database, not from the browser session', function () {
    $user = User::factory()->unverified()->create();

    // Sesi lama yang masih login tetapi belum terverifikasi dikeluarkan dan diarahkan ke halaman kode.
    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    $this->get('/verify-email')->assertRedirect(route('verifikasi-email.show'));
    $this->assertGuest();

    // Setelah terverifikasi (di mana pun), login berikutnya langsung berhasil.
    $user->markEmailAsVerified();

    $this->post('/login/asesi', ['email' => $user->email, 'password' => 'password'])->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($user);
});

test('the verification page cannot be opened without a pending account', function () {
    $this->get('/verifikasi-email')->assertRedirect(route('login.role', 'asesi'));
    $this->post('/verifikasi-email', ['kode' => '123456'])->assertRedirect(route('login.role', 'asesi'));
});
