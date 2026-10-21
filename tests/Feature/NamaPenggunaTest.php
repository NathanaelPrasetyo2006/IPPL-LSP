<?php

use App\Models\Asesor;
use App\Models\DataDiri;
use App\Models\User;

test('the profile menu receives the account name alongside the email', function (Closure $buatAkun, string $nama) {
    $user = $buatAkun();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('auth.user.name', $nama)
            ->where('auth.user.email', $user->email));
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), 'Admin LSP'],
    'asesor' => [fn () => Asesor::factory()->create(['nama' => 'Budi Santoso', 'id_user' => User::factory()->asesor()])->user, 'Budi Santoso'],
    'asesi' => [fn () => DataDiri::factory()->create(['nama_lengkap' => 'Sari Dewi'])->user, 'Sari Dewi'],
]);
