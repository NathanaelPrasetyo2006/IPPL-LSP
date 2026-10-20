<?php

use App\Models\User;
use App\Notifications\KodeOtpNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(fn () => Notification::fake());

/**
 * Kode OTP terakhir yang dikirim ke akun ini.
 */
function kodeOtpTerakhir(User $user): string
{
    $kode = null;

    Notification::assertSentTo($user, KodeOtpNotification::class, function (KodeOtpNotification $notifikasi) use (&$kode) {
        $kode = $notifikasi->kode;

        return true;
    });

    return $kode;
}

test('forgot password screen can be rendered for each role', function (string $role) {
    $this->get("/forgot-password/{$role}")->assertStatus(200);
})->with(['admin', 'asesor', 'asesi']);

test('leaving the forgot password flow clears the typed email, also across roles', function () {
    $user = User::factory()->asesor()->create();

    $this->post('/forgot-password/asesor', ['email' => $user->email])->assertRedirect(route('password.otp', 'asesor'));

    // Pindah ke lupa password peran lain lalu kembali: email lama tidak terbawa dan prosesnya dimulai ulang.
    $this->get('/forgot-password/asesi')->assertInertia(fn ($page) => $page->component('auth/forgot-password')->missing('email'));
    $this->get('/forgot-password/asesor')->assertInertia(fn ($page) => $page->missing('email'));
    $this->get('/forgot-password/asesor/kode')->assertRedirect(route('password.request', 'asesor'));
});

test('an unknown email or another role gets a clear error and no code', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => 'tidak-ada@example.com'])->assertSessionHasErrors('email');
    $this->post('/forgot-password/admin', ['email' => $user->email])->assertSessionHasErrors('email');

    Notification::assertNothingSent();
});

test('password is reset through email, OTP code, and a new password page', function () {
    $user = User::factory()->asesor()->create(['must_change_password' => true]);

    $this->post('/forgot-password/asesor', ['email' => $user->email])->assertRedirect(route('password.otp', 'asesor'));

    // Halaman password baru belum bisa dibuka sebelum kode benar.
    $this->get('/forgot-password/asesor/password-baru')->assertRedirect(route('password.request', 'asesor'));

    $this->post('/forgot-password/asesor/kode', ['kode' => '000000'])->assertSessionHasErrors('kode');
    $this->post('/forgot-password/asesor/kode', ['kode' => kodeOtpTerakhir($user)])->assertRedirect(route('password.reset', 'asesor'));

    $this->get('/forgot-password/asesor/password-baru')->assertOk();

    $this->post('/forgot-password/asesor/password-baru', ['password' => 'rahasia-baru', 'password_confirmation' => 'rahasia-baru'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('login.role', 'asesor'));

    $user->refresh();

    expect(Hash::check('rahasia-baru', $user->password))->toBeTrue()
        ->and($user->must_change_password)->toBeFalse();

    // Sesi lupa password selesai; halaman password baru tertutup lagi.
    $this->get('/forgot-password/asesor/password-baru')->assertRedirect(route('password.request', 'asesor'));
});

test('an OTP code expires after 60 seconds and can be used only once', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => $user->email]);
    $kode = kodeOtpTerakhir($user);

    $this->travel(61)->seconds();

    $this->post('/forgot-password/asesi/kode', ['kode' => $kode])
        ->assertSessionHasErrors(['kode' => 'Kode sudah kedaluwarsa. Silakan minta kode baru.']);
});

test('a code is voided after five wrong attempts', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => $user->email]);
    $kode = kodeOtpTerakhir($user);
    $salah = $kode === '111111' ? '222222' : '111111';

    foreach (range(1, 5) as $_) {
        $this->post('/forgot-password/asesi/kode', ['kode' => $salah]);
    }

    $this->post('/forgot-password/asesi/kode', ['kode' => $kode])->assertSessionHasErrors('kode');
});

test('resending waits 60 seconds and is limited to five times a day', function () {
    $user = User::factory()->create();

    $this->post('/forgot-password/asesi', ['email' => $user->email]);

    $this->post('/forgot-password/asesi/kirim-ulang')->assertSessionHasErrors('kode');

    foreach (range(1, 5) as $_) {
        $this->travel(61)->seconds();
        $this->post('/forgot-password/asesi/kirim-ulang')->assertSessionHasNoErrors();
    }

    $this->travel(61)->seconds();
    $this->post('/forgot-password/asesi/kirim-ulang')->assertSessionHasErrors('kode');

    Notification::assertSentToTimes($user, KodeOtpNotification::class, 6);

    // Batas harian kembali setelah berganti hari (WIB).
    $this->travel(1)->days();
    $this->post('/forgot-password/asesi/kirim-ulang')->assertSessionHasNoErrors();
});
