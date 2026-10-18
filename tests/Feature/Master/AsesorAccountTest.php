<?php

use App\Enums\UserRole;
use App\Models\Asesor;
use App\Models\User;
use App\Notifications\AsesorPasswordSementara;
use App\Notifications\AsesorVerifikasiAkun;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
});

function asesorBelumVerifikasi(array $user = []): Asesor
{
    return Asesor::factory()->create(['id_user' => User::factory()->asesor()->unverified()->create($user)]);
}

test('creating an asesor sends a verification link instead of a password', function () {
    $this->actingAs($this->admin)
        ->post('/master/asesor', ['nama' => 'Budi Santoso', 'no_reg' => 'MET.001.2026', 'email' => 'budi@example.com'])
        ->assertRedirect(route('master.asesor.index'));

    $user = User::firstWhere('email', 'budi@example.com');

    expect($user->role)->toBe(UserRole::Asesor)
        ->and($user->isVerified())->toBeFalse()
        ->and($user->asesor->nama)->toBe('Budi Santoso');

    Notification::assertSentTo($user, AsesorVerifikasiAkun::class);
    Notification::assertNotSentTo($user, AsesorPasswordSementara::class);
});

test('verification link activates the account and emails a temporary password once', function () {
    $user = asesorBelumVerifikasi()->user;
    $url = AsesorVerifikasiAkun::url($user);

    $this->get($url)->assertInertia(fn ($page) => $page->component('auth/asesor-verification')->where('status', 'activated'));

    $user->refresh();
    expect($user->isVerified())->toBeTrue()->and($user->must_change_password)->toBeTrue();

    // Membuka link yang sama lagi tidak membuat dan mengirim password baru.
    $this->get($url)->assertInertia(fn ($page) => $page->where('status', 'already'));

    Notification::assertSentToTimes($user, AsesorPasswordSementara::class, 1);
});

test('expired or tampered verification links do not activate the account', function () {
    $user = asesorBelumVerifikasi()->user;
    $url = AsesorVerifikasiAkun::url($user);

    $this->get(str_replace(sha1($user->email), sha1('lain@example.com'), $url))
        ->assertInertia(fn ($page) => $page->where('status', 'invalid'));

    $this->travel(AsesorVerifikasiAkun::BERLAKU_JAM + 1)->hours();

    $this->get($url)->assertInertia(fn ($page) => $page->where('status', 'invalid'));

    expect($user->fresh()->isVerified())->toBeFalse();
    Notification::assertNotSentTo($user, AsesorPasswordSementara::class);
});

test('unverified asesor can not log in even with the right password', function () {
    $user = asesorBelumVerifikasi()->user;

    $this->post('/login/asesor', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('admin can delete an unverified asesor permanently', function () {
    $asesor = asesorBelumVerifikasi();
    $user = $asesor->user;

    $this->actingAs($this->admin)->delete("/master/asesor/{$asesor->id_asesor}")->assertSessionHas('success');

    $this->assertModelMissing($asesor);
    $this->assertModelMissing($user);
});

test('admin can not delete a verified asesor', function () {
    $asesor = Asesor::factory()->create(['id_user' => User::factory()->asesor()]);

    $this->actingAs($this->admin)->delete("/master/asesor/{$asesor->id_asesor}")->assertSessionHas('error');

    $this->assertModelExists($asesor);
});

test('verification link can be resent only while the account is unverified', function () {
    $belum = asesorBelumVerifikasi();
    $sudah = Asesor::factory()->create(['id_user' => User::factory()->asesor()]);

    $this->actingAs($this->admin)->post("/master/asesor/{$belum->id_asesor}/kirim-ulang-verifikasi")->assertSessionHas('success');
    $this->actingAs($this->admin)->post("/master/asesor/{$sudah->id_asesor}/kirim-ulang-verifikasi")->assertSessionHas('error');

    Notification::assertSentTo($belum->user, AsesorVerifikasiAkun::class);
    Notification::assertNotSentTo($sudah->user, AsesorVerifikasiAkun::class);
});

test('correcting the email of an unverified asesor resends the verification link', function () {
    $asesor = asesorBelumVerifikasi(['email' => 'salah@example.com']);

    $this->actingAs($this->admin)->put("/master/asesor/{$asesor->id_asesor}", [
        'nama' => $asesor->nama,
        'no_reg' => $asesor->no_reg,
        'email' => 'benar@example.com',
    ])->assertRedirect(route('master.asesor.index'));

    $user = $asesor->user->fresh();

    expect($user->email)->toBe('benar@example.com');
    Notification::assertSentTo($user, AsesorVerifikasiAkun::class);
});
