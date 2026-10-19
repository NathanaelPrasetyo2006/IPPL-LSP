<?php

use App\Enums\StatusPermohonan;
use App\Models\DataDiri;
use App\Models\Negara;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\Tuk;
use App\Models\User;
use App\Notifications\LokasiPermohonanPerluDipilihUlang;
use App\Notifications\PermohonanApl01Masuk;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Notification::fake();

    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
    $this->dataDiri = DataDiri::factory()->create(['id_user' => $this->user->id_user, 'nama_lengkap' => 'Budi Santoso']);

    $this->skema = SkemaSertifikasi::factory()->create();
    $this->periode = PeriodePendaftaran::factory()->create();
    $this->periode->skema()->attach($this->skema->id_skema);

    $this->tuk = Tuk::factory()->create(['nama' => 'TUK Surabaya']);
    $this->tukLain = Tuk::factory()->create(['nama' => 'TUK Malang']);
    $this->periode->simpanLokasi([['id_tuk' => $this->tuk->id_tuk, 'skema' => [$this->skema->id_skema]]]);

    $this->idIndonesia = Negara::create(['nama_negara' => 'Indonesia'])->id_negara;
});

function urlDaftar(PeriodePendaftaran $periode, SkemaSertifikasi $skema): string
{
    return "/sertifikasi/daftar/{$periode->id_periode}/{$skema->id_skema}";
}

function dataApl01(array $override = []): array
{
    return [
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '3578010101900001',
        'tempat_lahir' => 'Surabaya',
        'tgl_lahir' => '1995-05-17',
        'jenis_kelamin' => 'L',
        'id_negara' => test()->idIndonesia,
        'alamat_ktp' => 'Jl. Pahlawan No. 1',
        'alamat_domisili' => 'Jl. Pahlawan No. 1',
        'provinsi' => 'Jawa Timur',
        'kota' => 'Kota Surabaya',
        'kecamatan' => 'Genteng',
        'kelurahan' => 'Embong Kaliasin',
        'kode_pos' => '60271',
        'no_telp' => '081234567890',
        'jenjang_pendidikan' => 'S1',
        'nama_sekolah' => 'Institut Teknologi Kalimantan',
        'pekerjaan' => 'Karyawan swasta',
        'tujuan_asesmen' => 'sertifikasi',
        'id_tuk' => test()->tuk->id_tuk,
        'pernyataan' => true,
        'dokumen' => [
            'ktp' => UploadedFile::fake()->create('ktp.jpg', 200, 'image/jpeg'),
            'pas_foto' => UploadedFile::fake()->create('foto.png', 200, 'image/png'),
            'ijazah' => UploadedFile::fake()->create('ijazah.pdf', 500, 'application/pdf'),
        ],
        ...$override,
    ];
}

test('asesi sees open periods with their schemes', function () {
    PeriodePendaftaran::factory()->ditutup()->create();

    $this->actingAs($this->user)
        ->get('/sertifikasi')
        ->assertInertia(fn ($page) => $page
            ->component('sertifikasi/index')
            ->has('periode', 1)
            ->where('periode.0.skema.0.id_skema', $this->skema->id_skema)
            ->where('periode.0.skema.0.sudah_mendaftar', false));
});

test('asesi submits APL-01 and documents are stored in their own folder', function () {
    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'dokumen' => [...dataApl01()['dokumen'], 'sertifikat_pelatihan' => UploadedFile::fake()->create('pelatihan.pdf', 100, 'application/pdf')],
        ]))
        ->assertSessionHasNoErrors();

    $permohonan = PermohonanSertifikasi::sole();
    $dataDiri = $this->dataDiri->fresh();
    $folder = "asesi/{$dataDiri->kode_asesi}_budi-santoso";

    expect($permohonan->status)->toBe(StatusPermohonan::Diajukan)
        ->and($permohonan->nomor)->toMatch('/^APL-\d{4}-\d{5}$/')
        ->and($dataDiri->nik)->toBe('3578010101900001')
        ->and($dataDiri->folder_dokumen)->toBe($folder)
        ->and($dataDiri->file_ktp)->toStartWith("{$folder}/identitas/ktp_")
        ->and($permohonan->dokumen)->toHaveCount(2)
        ->and($permohonan->dokumen->firstWhere('jenis.value', 'ijazah')->path)->toStartWith("{$folder}/apl-01/{$permohonan->nomor}/ijazah_");

    Storage::disk('local')->assertExists([$dataDiri->file_ktp, $dataDiri->file_pas_foto, ...$permohonan->dokumen->pluck('path')]);

    Notification::assertSentTo($this->admin, PermohonanApl01Masuk::class);
});

test('required documents and fields are validated', function () {
    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'nik' => '123',
            'kode_pos' => 'abc',
            'tujuan_asesmen' => 'lainnya',
            'pernyataan' => false,
            'dokumen' => ['ktp' => UploadedFile::fake()->create('ktp.exe', 10, 'application/x-msdownload')],
        ]))
        ->assertSessionHasErrors(['nik', 'kode_pos', 'tujuan_asesmen_lainnya', 'pernyataan', 'dokumen.ktp', 'dokumen.pas_foto', 'dokumen.ijazah']);

    expect(PermohonanSertifikasi::count())->toBe(0);
});

test('asesi cannot register twice for the same scheme while it is active', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01())
        ->assertRedirect(route('sertifikasi.index'))
        ->assertSessionHas('error');

    expect(PermohonanSertifikasi::count())->toBe(1);
});

test('a rejected asesi can apply again and reuse saved identity documents', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    PermohonanSertifikasi::sole()->update(['status' => StatusPermohonan::Ditolak]);

    $dokumen = dataApl01()['dokumen'];
    unset($dokumen['ktp'], $dokumen['pas_foto']);

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01(['dokumen' => $dokumen]))
        ->assertSessionHasNoErrors();

    expect(PermohonanSertifikasi::count())->toBe(2);
});

test('closed periods, unlisted schemes, and full quotas reject registration', function () {
    $skemaLain = SkemaSertifikasi::factory()->create();
    $ditutup = PeriodePendaftaran::factory()->ditutup()->create();
    $ditutup->skema()->attach($this->skema->id_skema);

    $this->actingAs($this->user)->get(urlDaftar($ditutup, $this->skema))->assertSessionHas('error');
    $this->actingAs($this->user)->get(urlDaftar($this->periode, $skemaLain))->assertSessionHas('error');

    $this->periode->update(['kuota' => 1]);
    PermohonanSertifikasi::factory()->create(['id_periode' => $this->periode->id_periode, 'id_skema' => $this->skema->id_skema]);

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01())
        ->assertSessionHas('error');

    expect(PermohonanSertifikasi::count())->toBe(1);
});

test('documents and applications are only visible to their owner and admin', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();
    $lain = User::factory()->create();

    $this->actingAs($this->user)->get("/sertifikasi/permohonan/{$permohonan->id_permohonan}")->assertOk();
    $this->actingAs($this->user)->get("/dokumen/permohonan/{$permohonan->id_permohonan}/ijazah")->assertOk();
    $this->actingAs($this->admin)->get("/dokumen/permohonan/{$permohonan->id_permohonan}/ktp")->assertOk();

    $this->actingAs($lain)->get("/sertifikasi/permohonan/{$permohonan->id_permohonan}")->assertNotFound();
    $this->actingAs($lain)->get("/dokumen/permohonan/{$permohonan->id_permohonan}/ijazah")->assertForbidden();
    $this->actingAs(User::factory()->asesor()->create())->get("/dokumen/permohonan/{$permohonan->id_permohonan}/ktp")->assertForbidden();
});

test('APL-01 form offers only the locations of the chosen scheme and stores the choice with history', function () {
    $this->actingAs($this->user)
        ->get(urlDaftar($this->periode, $this->skema))
        ->assertInertia(fn ($page) => $page->has('lokasi', 1)->where('lokasi.0.nama', 'TUK Surabaya'));

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01(['id_tuk' => $this->tukLain->id_tuk]))
        ->assertSessionHasErrors('id_tuk');

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01())
        ->assertSessionHasNoErrors();

    $permohonan = PermohonanSertifikasi::sole();

    expect($permohonan->id_tuk)->toBe($this->tuk->id_tuk)
        ->and($permohonan->riwayatLokasi)->toHaveCount(1)
        ->and($permohonan->riwayatLokasi[0]->id_tuk_lama)->toBeNull()
        ->and($permohonan->riwayatLokasi[0]->id_tuk_baru)->toBe($this->tuk->id_tuk)
        ->and($permohonan->riwayatLokasi[0]->id_user)->toBe($this->user->id_user);
});

test('removing a location from a period asks its active applicants to choose again', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $aktif = PermohonanSertifikasi::sole();

    $ditolak = PermohonanSertifikasi::factory()->create([
        'id_periode' => $this->periode->id_periode,
        'id_skema' => $this->skema->id_skema,
        'id_tuk' => $this->tuk->id_tuk,
        'status' => StatusPermohonan::Ditolak,
    ]);

    // Admin mengganti TUK Surabaya dengan TUK Malang.
    $this->actingAs($this->admin)
        ->put("/pendaftaran/periode/{$this->periode->id_periode}", [
            'nama' => $this->periode->nama,
            'tanggal_buka' => $this->periode->tanggal_buka->toDateString(),
            'tanggal_tutup' => $this->periode->tanggal_tutup->toDateString(),
            'aktif' => true,
            'skema' => [$this->skema->id_skema],
            'lokasi' => [['id_tuk' => $this->tukLain->id_tuk, 'skema' => [$this->skema->id_skema]]],
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success', fn (string $pesan) => str_contains($pesan, '1 pendaftar'));

    $aktif->refresh();

    expect($aktif->id_tuk)->toBeNull()
        ->and($aktif->perluPilihLokasi())->toBeTrue()
        ->and($aktif->riwayatLokasi)->toHaveCount(2)
        ->and($aktif->riwayatLokasi[1]->id_tuk_lama)->toBe($this->tuk->id_tuk)
        ->and($aktif->riwayatLokasi[1]->id_user)->toBe($this->admin->id_user)
        ->and($ditolak->fresh()->id_tuk)->toBe($this->tuk->id_tuk);

    Notification::assertSentTo($this->user, LokasiPermohonanPerluDipilihUlang::class);
});

test('asesi chooses a new location only when needed and only from available ones', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();
    $url = "/sertifikasi/permohonan/{$permohonan->id_permohonan}/lokasi";

    // Lokasi masih ada, jadi belum perlu memilih ulang.
    $this->actingAs($this->user)->put($url, ['id_tuk' => $this->tuk->id_tuk])->assertSessionHas('error');

    $this->periode->simpanLokasi([['id_tuk' => $this->tukLain->id_tuk, 'skema' => [$this->skema->id_skema]]]);
    $this->periode->kosongkanLokasiTidakTersedia($this->admin);

    $this->actingAs($this->user)
        ->get("/sertifikasi/permohonan/{$permohonan->id_permohonan}")
        ->assertInertia(fn ($page) => $page
            ->where('permohonan.perlu_pilih_lokasi', true)
            ->has('lokasiTersedia', 1)
            ->where('lokasiTersedia.0.nama', 'TUK Malang')
            ->has('permohonan.riwayat_lokasi', 2));

    $this->actingAs(User::factory()->create())->put($url, ['id_tuk' => $this->tukLain->id_tuk])->assertNotFound();
    $this->actingAs($this->user)->put($url, ['id_tuk' => $this->tuk->id_tuk])->assertSessionHasErrors('id_tuk');

    $this->actingAs($this->user)->put($url, ['id_tuk' => $this->tukLain->id_tuk])->assertSessionHas('success');

    $permohonan->refresh();

    expect($permohonan->id_tuk)->toBe($this->tukLain->id_tuk)
        ->and($permohonan->riwayatLokasi)->toHaveCount(3)
        ->and($permohonan->riwayatLokasi[2]->keterangan)->toBe('Dipilih ulang oleh asesi.');
});
