<?php

use App\Enums\StatusPermohonan;
use App\Models\DataDiri;
use App\Models\Negara;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\User;
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
