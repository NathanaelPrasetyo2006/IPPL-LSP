<?php

use App\Models\Asesor;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\Tuk;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

test('urls use a random code instead of the sequential id', function () {
    $permohonan = PermohonanSertifikasi::factory()->create();

    $url = route('sertifikasi.show', $permohonan);

    expect($permohonan->ulid)->toMatch('/^[0-9a-z]{26}$/')
        ->and($url)->toEndWith("/sertifikasi/permohonan/{$permohonan->ulid}")
        ->and($url)->not->toContain("/{$permohonan->id_permohonan}");
});

test('sequential ids in the url are no longer accepted', function () {
    $admin = User::factory()->admin()->create();
    $permohonan = PermohonanSertifikasi::factory()->create();
    $pemilik = $permohonan->dataDiri->user;

    $this->actingAs($pemilik)->get("/sertifikasi/permohonan/{$permohonan->id_permohonan}")->assertNotFound();
    $this->actingAs($pemilik)->get("/sertifikasi/permohonan/{$permohonan->ulid}")->assertOk();

    foreach ([
        '/pendaftaran/verifikasi/'.$permohonan->id_permohonan,
        '/pendaftaran/periode/'.PeriodePendaftaran::factory()->create()->id_periode,
        '/master/tuk/'.Tuk::factory()->create()->id_tuk.'/edit',
        '/master/skema-sertifikasi/'.SkemaSertifikasi::factory()->create()->id_skema.'/edit',
        '/master/asesor/'.Asesor::factory()->create()->id_asesor.'/edit',
    ] as $url) {
        $this->actingAs($admin)->get($url)->assertNotFound();
    }
});

test('every routed record gets its own code automatically', function () {
    $kode = collect([
        User::factory()->create(),
        Asesor::factory()->create(),
        SkemaSertifikasi::factory()->create(),
        Tuk::factory()->create(),
        PeriodePendaftaran::factory()->create(),
        PermohonanSertifikasi::factory()->create(),
    ])->pluck('ulid');

    expect($kode->filter())->toHaveCount(6)
        ->and($kode->unique())->toHaveCount(6);
});

test('stored notification links are moved from ids to codes', function () {
    $permohonan = PermohonanSertifikasi::factory()->create();
    $user = User::factory()->create();

    DB::table('notifications')->insert([
        'id' => (string) Str::uuid(),
        'type' => 'test',
        'notifiable_type' => User::class,
        'notifiable_id' => $user->id_user,
        'data' => json_encode(['judul' => 'Lama', 'url' => "/sertifikasi/permohonan/{$permohonan->id_permohonan}"]),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    (require database_path('migrations/2026_10_20_090100_update_notification_urls_to_ulid.php'))->up();

    expect(json_decode(DB::table('notifications')->value('data'), true)['url'])->toBe("/sertifikasi/permohonan/{$permohonan->ulid}");
});
