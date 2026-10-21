<?php

use App\Enums\UserRole;
use App\Models\Asesor;
use App\Models\User;
use App\Notifications\AsesorVerifikasiAkun;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
});

function asesorBelumVerifikasi(array $user = []): Asesor
{
    return Asesor::factory()->create(['id_user' => User::factory()->asesor()->unverified()->create($user)]);
}

/**
 * Ambil password sementara yang tercantum di email aktivasi terakhir untuk user ini.
 */
function passwordDariEmail(User $user): ?string
{
    $password = null;

    Notification::assertSentTo($user, AsesorVerifikasiAkun::class, function ($notification) use ($user, &$password) {
        preg_match('/`(.+)`/', implode("\n", $notification->toMail($user)->introLines), $match);
        $password = $match[1] ?? $password;

        return true;
    });

    return $password;
}

test('creating an asesor sends one email with the temporary password and verification link', function () {
    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Budi Santoso', 'no_reg' => 'MET.001.2026', 'email' => 'budi@example.com'])
        ->assertRedirect(route('master.asesor.index'));

    $user = User::firstWhere('email', 'budi@example.com');

    expect($user->role)->toBe(UserRole::Asesor)
        ->and($user->isVerified())->toBeFalse()
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->asesor->nama)->toBe('Budi Santoso');

    Notification::assertSentToTimes($user, AsesorVerifikasiAkun::class, 1);

    $mail = Notification::sent($user, AsesorVerifikasiAkun::class)->first()->toMail($user);
    expect(implode("\n", $mail->introLines))->toContain('budi@example.com')->toContain('Password sementara')
        ->and($mail->actionUrl)->toContain('/asesor/verifikasi/');
});

test('the emailed password only works after the verification link is clicked', function () {
    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Budi Santoso', 'no_reg' => 'MET.001.2026', 'email' => 'budi@example.com']);
    auth()->logout();

    $user = User::firstWhere('email', 'budi@example.com');
    $kredensial = ['email' => 'budi@example.com', 'password' => passwordDariEmail($user)];

    $this->post('/login/asesor', $kredensial)->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->get(AsesorVerifikasiAkun::url($user))->assertInertia(fn ($page) => $page->where('status', 'activated'));

    $this->post('/login/asesor', $kredensial)->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);
});

test('verification link activates the account once without changing the password', function () {
    $user = asesorBelumVerifikasi()->user;
    $hash = $user->password;
    $url = AsesorVerifikasiAkun::url($user);

    $this->get($url)->assertInertia(fn ($page) => $page->component('auth/asesor-verification')->where('status', 'activated'));

    $user->refresh();
    expect($user->isVerified())->toBeTrue()->and($user->password)->toBe($hash);

    $this->get($url)->assertInertia(fn ($page) => $page->where('status', 'already'));

    Notification::assertNothingSentTo($user);
});

test('expired or tampered verification links do not activate the account', function () {
    $user = asesorBelumVerifikasi()->user;
    $url = AsesorVerifikasiAkun::url($user);

    $this->get(str_replace(sha1($user->email), sha1('lain@example.com'), $url))
        ->assertInertia(fn ($page) => $page->where('status', 'invalid'));

    $this->travel(AsesorVerifikasiAkun::BERLAKU_JAM + 1)->hours();

    $this->get($url)->assertInertia(fn ($page) => $page->where('status', 'invalid'));

    expect($user->fresh()->isVerified())->toBeFalse();
});

test('unverified asesor can not log in even with the right password', function () {
    $user = asesorBelumVerifikasi()->user;

    $this->post('/login/asesor', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('admin can delete an unverified asesor permanently', function () {
    $asesor = asesorBelumVerifikasi();
    $user = $asesor->user;

    $this->actingAs($this->admin)->delete("/master/asesor/{$asesor->ulid}")->assertSessionHas('success');

    $this->assertModelMissing($asesor);
    $this->assertModelMissing($user);
});

test('admin can not delete a verified asesor', function () {
    $asesor = Asesor::factory()->create(['id_user' => User::factory()->asesor()]);

    $this->actingAs($this->admin)->delete("/master/asesor/{$asesor->ulid}")->assertSessionHas('error');

    $this->assertModelExists($asesor);
});

test('verification link can be resent only while the account is unverified', function () {
    $belum = asesorBelumVerifikasi();
    $sudah = Asesor::factory()->create(['id_user' => User::factory()->asesor()]);

    $this->actingAs($this->admin)->post("/master/asesor/{$belum->ulid}/kirim-ulang-verifikasi")->assertSessionHas('success');
    $this->actingAs($this->admin)->post("/master/asesor/{$sudah->ulid}/kirim-ulang-verifikasi")->assertSessionHas('error');

    Notification::assertSentTo($belum->user, AsesorVerifikasiAkun::class);
    Notification::assertNotSentTo($sudah->user, AsesorVerifikasiAkun::class);

    // Password sementara dibuat ulang setiap kirim ulang dan tercantum di email yang baru.
    expect(Hash::check(passwordDariEmail($belum->user), $belum->user->fresh()->password))->toBeTrue();
});

test('correcting the email of an unverified asesor resends the verification link', function () {
    $asesor = asesorBelumVerifikasi(['email' => 'salah@example.com']);

    $this->actingAs($this->admin)->put("/master/asesor/{$asesor->ulid}", [
        'nama' => $asesor->nama,
        'no_reg' => $asesor->no_reg,
        'email' => 'benar@example.com',
    ])->assertRedirect(route('master.asesor.index'));

    $user = $asesor->user->fresh();

    expect($user->email)->toBe('benar@example.com');
    Notification::assertSentTo($user, AsesorVerifikasiAkun::class);
});

test('every new asesor gets a unique automatic kode asesor', function () {
    foreach (['budi@example.com' => 'MET.001.2026', 'sari@example.com' => 'MET.002.2026'] as $email => $noReg) {
        $this->actingAs($this->admin)->post('/master/asesor', ['nama' => 'Asesor', 'no_reg' => $noReg, 'email' => $email]);
    }

    $kode = Asesor::query()->orderBy('id_asesor')->pluck('kode_asesor');

    expect($kode)->toHaveCount(2)
        ->and($kode->unique())->toHaveCount(2)
        ->and($kode[0])->toMatch('/^ASR-\d{4}-\d{5}$/');
});

test('no. MET must be unique regardless of letter case', function () {
    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Budi', 'no_reg' => ' met.001.2026 ', 'email' => 'budi@example.com'])
        ->assertSessionHasNoErrors();

    expect(Asesor::sole()->no_reg)->toBe('MET.001.2026');

    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Sari', 'no_reg' => 'MET.001.2026', 'email' => 'sari@example.com'])
        ->assertSessionHasErrors('no_reg');

    expect(Asesor::count())->toBe(1);
});

test('no. MET may not contain spaces', function () {
    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Budi', 'no_reg' => 'MET.001 .2026', 'email' => 'budi@example.com'])
        ->assertSessionHasErrors(['no_reg' => 'No. registrasi/MET tidak boleh mengandung spasi.']);

    expect(Asesor::count())->toBe(0);
});
