<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\VerifikasiEmailAsesi;
use Illuminate\Support\Facades\Notification;

function dataRegistrasi(array $override = []): array
{
    return [
        'nama_lengkap' => 'Siti Aminah',
        'no_telp' => '0812-3456-7890',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
        'persetujuan' => true,
        ...$override,
    ];
}

beforeEach(fn () => Notification::fake());

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('calon asesi registers with a profile and must verify their email', function () {
    $response = $this->post('/register', dataRegistrasi());

    $this->assertAuthenticated();
    $response->assertRedirect(route('verification.notice', absolute: false));

    $user = User::firstWhere('email', 'test@example.com');

    expect($user->role)->toBe(UserRole::Asesi)
        ->and($user->hasVerifiedEmail())->toBeFalse()
        ->and($user->dataDiri->nama_lengkap)->toBe('Siti Aminah')
        ->and($user->dataDiri->no_telp)->toBe('081234567890')
        ->and($user->dataDiri->kode_asesi)->toMatch('/^ASI-\d{4}-\d{5}$/');

    Notification::assertSentTo($user, VerifikasiEmailAsesi::class);
});

test('registration requires name, phone, and consent', function () {
    $this->post('/register', dataRegistrasi(['nama_lengkap' => '', 'no_telp' => 'abc', 'persetujuan' => false]))
        ->assertSessionHasErrors(['nama_lengkap', 'no_telp', 'persetujuan']);

    $this->assertGuest();
});

test('registration ignores a role sent by the client', function () {
    $this->post('/register', dataRegistrasi(['role' => 'admin']));

    expect(User::firstWhere('email', 'test@example.com')->role)->toBe(UserRole::Asesi);
});

test('unverified asesi cannot open the certification menu yet', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/sertifikasi')->assertRedirect(route('verification.notice'));
});
