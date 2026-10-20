<?php

use App\Models\Asesor;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->asesor = Asesor::factory()->create(['id_user' => User::factory()->asesor()->create()]);
    $this->user = $this->asesor->user;
});

/**
 * PNG 1×1 asli; UploadedFile::fake()->image() butuh ekstensi GD yang tidak selalu terpasang.
 */
function pasFoto(string $nama = 'foto.png'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $nama,
        base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg=='),
    );
}

function biodataValid(array $override = []): array
{
    return [
        'nik' => '3578010101900001',
        'tempat_lahir' => 'Surabaya',
        'tgl_lahir' => '1990-01-01',
        'jenis_kelamin' => 'L',
        'no_telp' => '0812-3456-7890',
        'alamat' => 'Jl. Pahlawan No. 1',
        'provinsi' => 'Jawa Timur',
        'kota' => 'Kota Surabaya',
        'kecamatan' => 'Genteng',
        'kelurahan' => 'Embong Kaliasin',
        'pendidikan_terakhir' => 'S1',
        'instansi' => 'PT Contoh',
        'bidang_keahlian' => 'Teknologi Informasi',
        'masa_berlaku_sertifikat' => now()->addYears(2)->toDateString(),
        'pas_foto' => pasFoto(),
        ...$override,
    ];
}

test('asesor can open the biodata form', function () {
    $this->actingAs($this->user)
        ->get('/asesor/biodata')
        ->assertInertia(fn ($page) => $page
            ->component('asesor/biodata')
            ->where('asesor.no_reg', $this->asesor->no_reg)
            ->where('asesor.kode_asesor', $this->asesor->fresh()->kode_asesor)
            ->where('asesor.lengkap', false));
});

test('only asesor can access the biodata and card pages', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/asesor/biodata')->assertForbidden();
    $this->actingAs($admin)->get('/asesor/kartu')->assertForbidden();
    $this->actingAs($admin)->put('/asesor/biodata', biodataValid())->assertForbidden();
});

test('asesor can complete biodata with a pas foto', function () {
    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid())
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('asesor.biodata.edit'));

    $asesor = $this->asesor->fresh();

    expect($asesor->nik)->toBe('3578010101900001')
        ->and($asesor->no_telp)->toBe('081234567890')
        ->and($asesor->biodataLengkap())->toBeTrue()
        ->and($asesor->biodata_diperbarui_pada)->not->toBeNull();

    Storage::disk('local')->assertExists($asesor->file_pas_foto);

    $this->actingAs($this->user)->get('/asesor/biodata/foto')->assertOk();
});

test('asesor cannot change their own name or registration number', function () {
    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid(['nama' => 'Nama Palsu', 'no_reg' => 'MET.PALSU']));

    $asesor = $this->asesor->fresh();

    expect($asesor->nama)->toBe($this->asesor->nama)
        ->and($asesor->no_reg)->toBe($this->asesor->no_reg);
});

test('biodata is validated', function () {
    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid([
            'nik' => '123',
            'tgl_lahir' => now()->subYears(10)->toDateString(),
            'jenis_kelamin' => 'X',
            'no_telp' => 'abc',
            'kelurahan' => '',
            'pas_foto' => null,
        ]))
        ->assertSessionHasErrors(['nik', 'tgl_lahir', 'jenis_kelamin', 'no_telp', 'kelurahan', 'pas_foto']);
});

test('nik must be unique between asesor', function () {
    Asesor::factory()->create(['nik' => '3578010101900001']);

    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid())
        ->assertSessionHasErrors('nik');
});

test('pas foto is optional once uploaded and is replaced when a new one is sent', function () {
    $this->actingAs($this->user)->put('/asesor/biodata', biodataValid());
    $fotoLama = $this->asesor->fresh()->file_pas_foto;

    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid(['pas_foto' => null, 'kota' => 'Kota Malang']))
        ->assertSessionHasNoErrors();

    expect($this->asesor->fresh())
        ->kota->toBe('Kota Malang')
        ->file_pas_foto->toBe($fotoLama);

    $this->actingAs($this->user)
        ->put('/asesor/biodata', biodataValid(['pas_foto' => pasFoto('baru.png')]))
        ->assertSessionHasNoErrors();

    Storage::disk('local')->assertMissing($fotoLama);
    Storage::disk('local')->assertExists($this->asesor->fresh()->file_pas_foto);
});

test('card page lists missing biodata until it is complete', function () {
    $this->actingAs($this->user)
        ->get('/asesor/kartu')
        ->assertInertia(fn ($page) => $page
            ->component('asesor/kartu')
            ->where('asesor.lengkap', false)
            ->where('biodata_wajib_kosong', fn ($kosong) => collect($kosong)->contains('file_pas_foto')));

    $this->actingAs($this->user)->put('/asesor/biodata', biodataValid());

    $this->actingAs($this->user)
        ->get('/asesor/kartu')
        ->assertInertia(fn ($page) => $page
            ->where('asesor.lengkap', true)
            ->where('asesor.kedaluwarsa', false)
            ->where('biodata_wajib_kosong', [])
            ->whereNot('asesor.foto_url', null));
});

test('card marks an expired asesor certificate', function () {
    $this->actingAs($this->user)->put('/asesor/biodata', biodataValid([
        'masa_berlaku_sertifikat' => now()->subDay()->toDateString(),
    ]));

    $this->actingAs($this->user)
        ->get('/asesor/kartu')
        ->assertInertia(fn ($page) => $page->where('asesor.kedaluwarsa', true));
});
