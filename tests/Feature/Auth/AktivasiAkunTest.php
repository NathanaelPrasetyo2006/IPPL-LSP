<?php

use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create([
        'email' => 'siti@example.com',
        'nonaktif_pada' => now()->subMonth(),
        'email_verified_at' => null,
    ]);
});

function kodeAktivasi(User $user): string
{
    $kode = null;

    Notification::assertSentTo($user, KodeOtpNotification::class, function (KodeOtpNotification $notifikasi) use (&$kode) {
        $kode = $notifikasi->kode;

        return true;
    });

    return $kode;
}

test('logging in to a deactivated account leads to reactivation with an OTP code', function () {
    $this->post('/login/asesi', ['email' => 'siti@example.com', 'password' => 'password'])->assertRedirect(route('aktivasi.show'));
    $this->assertGuest();

    $this->get('/aktivasi-akun')->assertInertia(fn ($page) => $page->component('auth/aktivasi-akun')->where('asal', 'login'));

    $this->post('/aktivasi-akun/kirim')->assertSessionHasNoErrors();
    $this->post('/aktivasi-akun', ['kode' => kodeAktivasi($this->user)])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);

    $this->user->refresh();

    expect($this->user->isNonaktif())->toBeFalse()
        ->and($this->user->email_verified_at)->not->toBeNull();
});

test('a wrong password on a deactivated account is just a failed login', function () {
    $this->post('/login/asesi', ['email' => 'siti@example.com', 'password' => 'salah'])->assertSessionHasErrors('email');

    $this->get('/aktivasi-akun')->assertRedirect(route('login.role', 'asesi'));
});

test('registering with the email of a deactivated account reactivates it with the new password', function () {
    $this->post('/register', [
        'nama_lengkap' => 'Siti Aminah',
        'no_telp' => '081234567890',
        'email' => 'siti@example.com',
        'password' => 'password-baru',
        'password_confirmation' => 'password-baru',
        'persetujuan' => true,
    ])->assertRedirect(route('aktivasi.show'));

    expect(User::where('email', 'siti@example.com')->count())->toBe(1);

    $this->post('/aktivasi-akun/kirim');
    $this->post('/aktivasi-akun', ['kode' => kodeAktivasi($this->user)])->assertRedirect(route('dashboard'));

    expect(Hash::check('password-baru', $this->user->fresh()->password))->toBeTrue();
});

test('registering with the email of an active account is still rejected', function () {
    $aktif = User::factory()->create();

    $this->post('/register', [
        'nama_lengkap' => 'Orang Lain',
        'no_telp' => '081234567890',
        'email' => $aktif->email,
        'password' => 'password',
        'password_confirmation' => 'password',
        'persetujuan' => true,
    ])->assertSessionHasErrors('email');
});

test('a session of a deactivated account is ended on the next request', function () {
    $user = User::factory()->create();

    $this->actingAs($user);
    $user->forceFill(['nonaktif_pada' => now()])->save();

    $this->get('/dashboard')->assertRedirect(route('login.role', 'asesi'));
    $this->assertGuest();
});
