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
    return "/sertifikasi/daftar/{$periode->ulid}/{$skema->ulid}";
}

function dataApl01(array $override = []): array
{
    return [
        'nama_lengkap' => 'Budi Santoso',
        'nik' => '3578010101900001',
        'tempat_lahir' => 'Surabaya',
        'tgl_lahir' => '1995-05-17',
        'jenis_kelamin' => 'L',
        'kebangsaan' => 'wni',
        'id_negara' => test()->idIndonesia,
        'alamat_ktp' => 'Jl. Pahlawan No. 1',
        'provinsi_ktp' => 'Jawa Timur',
        'kota_ktp' => 'Kota Surabaya',
        'kecamatan_ktp' => 'Genteng',
        'kelurahan_ktp' => 'Embong Kaliasin',
        'alamat_domisili' => 'Jl. Pahlawan No. 1',
        'provinsi' => 'Jawa Timur',
        'kota' => 'Kota Surabaya',
        'kecamatan' => 'Genteng',
        'kelurahan' => 'Embong Kaliasin',
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
            ->where('periode.0.skema.0.keadaan', 'daftar'));
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
            'kota_ktp' => '',
            'tujuan_asesmen' => 'lainnya',
            'pernyataan' => false,
            'dokumen' => ['ktp' => UploadedFile::fake()->create('ktp.exe', 10, 'application/x-msdownload')],
        ]))
        ->assertSessionHasErrors(['nik', 'kota_ktp', 'tujuan_asesmen_lainnya', 'pernyataan', 'dokumen.ktp', 'dokumen.pas_foto', 'dokumen.ijazah']);

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

test('a rejected application is fixed and resubmitted as the same application', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();
    $ijazahLama = $permohonan->dokumen->firstWhere('jenis.value', 'ijazah')->path;

    $permohonan->update(['status' => StatusPermohonan::Ditolak, 'catatan_verifikasi' => 'Transkrip belum ada.', 'diverifikasi_pada' => now()]);
    $permohonan->catatStatus(StatusPermohonan::Ditolak, 'Transkrip belum ada.', $this->admin);

    // Mendaftar skema yang sama diarahkan ke perbaikan, bukan permohonan baru.
    $this->actingAs($this->user)->get(urlDaftar($this->periode, $this->skema))->assertRedirect(route('sertifikasi.perbaiki', $permohonan));

    $this->actingAs($this->user)
        ->get("/sertifikasi/permohonan/{$permohonan->ulid}/perbaiki")
        ->assertInertia(fn ($page) => $page
            ->component('sertifikasi/create')
            ->where('perbaikan.nomor', $permohonan->nomor)
            ->where('perbaikan.catatan_verifikasi', 'Transkrip belum ada.')
            ->whereNot('dokumenTersimpan.ijazah', null));

    // Ijazah tidak perlu diunggah ulang; hanya transkrip yang ditambahkan.
    $this->actingAs($this->user)
        ->post("/sertifikasi/permohonan/{$permohonan->ulid}/perbaiki", dataApl01([
            'dokumen' => ['transkrip' => UploadedFile::fake()->create('transkrip.pdf', 300, 'application/pdf')],
        ]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('sertifikasi.show', $permohonan));

    $permohonan->refresh();

    expect(PermohonanSertifikasi::count())->toBe(1)
        ->and($permohonan->status)->toBe(StatusPermohonan::Diajukan)
        ->and($permohonan->catatan_verifikasi)->toBeNull()
        ->and($permohonan->dokumen->firstWhere('jenis.value', 'ijazah')->path)->toBe($ijazahLama)
        ->and($permohonan->dokumen->firstWhere('jenis.value', 'transkrip')->path)->toStartWith(dirname($ijazahLama).'/transkrip_')
        ->and($permohonan->riwayatStatus->pluck('status')->map->value->all())->toBe(['diajukan', 'ditolak', 'diajukan'])
        ->and($permohonan->riwayatStatus[1]->catatan)->toBe('Transkrip belum ada.');

    Storage::disk('local')->assertExists($ijazahLama);
});

test('replacing a document while fixing overwrites the old file in the same folder', function (string $jenis) {
    $berkas = fn (string $nama) => UploadedFile::fake()->create("{$nama}.pdf", 300, 'application/pdf');

    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01([
        'dokumen' => [...dataApl01()['dokumen'], $jenis => $berkas("{$jenis}-lama")],
    ]));
    $permohonan = PermohonanSertifikasi::sole();
    $pathLama = $permohonan->dokumen()->where('jenis', $jenis)->value('path');
    $permohonan->update(['status' => StatusPermohonan::Ditolak]);

    $this->actingAs($this->user)
        ->post("/sertifikasi/permohonan/{$permohonan->ulid}/perbaiki", dataApl01(['dokumen' => [$jenis => $berkas("{$jenis}-baru")]]))
        ->assertSessionHasNoErrors();

    $dokumen = $permohonan->dokumen()->where('jenis', $jenis)->get();

    // Satu file per jenis, di folder permohonan yang sama; file lama dihapus.
    expect($dokumen)->toHaveCount(1)
        ->and($dokumen[0]->nama_asli)->toBe("{$jenis}-baru.pdf")
        ->and(dirname($dokumen[0]->path))->toBe(dirname($pathLama))
        // Tidak ada folder APL kedua di folder apl-01 asesi.
        ->and(Storage::disk('local')->directories(dirname(dirname($pathLama))))->toHaveCount(1);
    Storage::disk('local')->assertMissing($pathLama);
})->with(['ijazah', 'transkrip', 'sertifikat_pelatihan', 'surat_keterangan_kerja', 'portofolio']);

test('only rejected applications can be fixed', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();

    $this->actingAs($this->user)->get("/sertifikasi/permohonan/{$permohonan->ulid}/perbaiki")->assertSessionHas('error');
    $this->actingAs(User::factory()->create())->get("/sertifikasi/permohonan/{$permohonan->ulid}/perbaiki")->assertNotFound();
});

test('asesi can register only one scheme per period', function () {
    $skemaLain = SkemaSertifikasi::factory()->create();
    $this->periode->skema()->attach($skemaLain->id_skema);
    $this->periode->simpanLokasi([['id_tuk' => $this->tuk->id_tuk, 'skema' => [$this->skema->id_skema, $skemaLain->id_skema]]]);

    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());

    $this->actingAs($this->user)
        ->get('/sertifikasi')
        ->assertInertia(fn ($page) => $page
            ->where('periode.0.skema_terdaftar', $this->skema->judul)
            ->where('periode.0.skema', fn ($daftar) => collect($daftar)->pluck('keadaan', 'id_skema')->all() == [
                $this->skema->id_skema => 'terdaftar',
                $skemaLain->id_skema => 'terkunci',
            ]));

    $this->actingAs($this->user)->get(urlDaftar($this->periode, $skemaLain))->assertSessionHas('error');
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $skemaLain), dataApl01())->assertSessionHas('error');

    expect(PermohonanSertifikasi::count())->toBe(1);
});

test('asesi can cancel a pending application and then choose another scheme', function () {
    $skemaLain = SkemaSertifikasi::factory()->create();
    $this->periode->skema()->attach($skemaLain->id_skema);
    $this->periode->simpanLokasi([['id_tuk' => $this->tuk->id_tuk, 'skema' => [$this->skema->id_skema, $skemaLain->id_skema]]]);

    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();

    $this->actingAs(User::factory()->create())->post("/sertifikasi/permohonan/{$permohonan->ulid}/batal")->assertNotFound();

    $this->actingAs($this->user)->post("/sertifikasi/permohonan/{$permohonan->ulid}/batal")->assertRedirect(route('sertifikasi.index'));

    expect($permohonan->fresh()->status)->toBe(StatusPermohonan::Dibatalkan)
        ->and($this->periode->kuotaTerpakai())->toBe(0);

    $this->actingAs($this->user)->post(urlDaftar($this->periode, $skemaLain), dataApl01())->assertSessionHasNoErrors();

    expect(PermohonanSertifikasi::where('status', 'diajukan')->sole()->id_skema)->toBe($skemaLain->id_skema);
});

test('a cancelled application is resubmitted as the same application with its documents', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();
    $ijazah = $permohonan->dokumen()->where('jenis', 'ijazah')->value('path');

    $this->actingAs($this->user)->post("/sertifikasi/permohonan/{$permohonan->ulid}/batal");

    // Mendaftar skema yang sama lagi membuka permohonan lama, bukan membuat yang baru.
    $this->actingAs($this->user)->get(urlDaftar($this->periode, $this->skema))->assertRedirect(route('sertifikasi.perbaiki', $permohonan));
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01())->assertSessionHas('error');

    $this->actingAs($this->user)
        ->get(route('sertifikasi.perbaiki', $permohonan))
        ->assertInertia(fn ($page) => $page
            ->where('perbaikan.status', 'dibatalkan')
            ->where('dokumenTersimpan.ijazah', route('dokumen.permohonan', [$permohonan, 'ijazah'])));

    $this->actingAs($this->user)
        ->post(route('sertifikasi.perbaiki.store', $permohonan), dataApl01(['dokumen' => []]))
        ->assertSessionHasNoErrors();

    $permohonan->refresh();

    expect(PermohonanSertifikasi::count())->toBe(1)
        ->and($permohonan->status)->toBe(StatusPermohonan::Diajukan)
        ->and($permohonan->dokumen()->where('jenis', 'ijazah')->value('path'))->toBe($ijazah)
        ->and($permohonan->riwayatStatus->last()->catatan)->toBe('Diajukan ulang setelah dibatalkan.');

    Storage::disk('local')->assertExists($ijazah);
});

test('ijazah from a previous application is reused when it is not uploaded again', function () {
    $skemaLain = SkemaSertifikasi::factory()->create();
    $this->periode->skema()->attach($skemaLain->id_skema);
    $this->periode->simpanLokasi([['id_tuk' => $this->tuk->id_tuk, 'skema' => [$this->skema->id_skema, $skemaLain->id_skema]]]);

    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $lama = PermohonanSertifikasi::sole();
    $this->actingAs($this->user)->post("/sertifikasi/permohonan/{$lama->ulid}/batal");

    // Form permohonan baru menampilkan ijazah yang sudah tersimpan, tetapi bukan dokumen lain yang khusus per skema.
    $this->actingAs($this->user)
        ->get(urlDaftar($this->periode, $skemaLain))
        ->assertInertia(fn ($page) => $page
            ->where('dokumenTersimpan.ijazah', route('dokumen.permohonan', [$lama, 'ijazah']))
            ->where('dokumenTersimpan.portofolio', null));

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $skemaLain), dataApl01(['dokumen' => []]))
        ->assertSessionHasNoErrors();

    $baru = PermohonanSertifikasi::where('status', 'diajukan')->sole();
    $ijazahLama = $lama->dokumen()->where('jenis', 'ijazah')->value('path');
    $ijazahBaru = $baru->dokumen()->where('jenis', 'ijazah')->value('path');

    // File disalin ke folder permohonan baru, jadi file lama tetap utuh.
    expect($ijazahBaru)->toStartWith($baru->folderDokumen().'/ijazah_')->not->toBe($ijazahLama);
    Storage::disk('local')->assertExists([$ijazahLama, $ijazahBaru]);
});

test('an application that is already verified cannot be cancelled by the asesi', function () {
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01());
    $permohonan = PermohonanSertifikasi::sole();
    $permohonan->update(['status' => StatusPermohonan::Diterima]);

    $this->actingAs($this->user)->post("/sertifikasi/permohonan/{$permohonan->ulid}/batal")->assertSessionHas('error');

    expect($permohonan->fresh()->status)->toBe(StatusPermohonan::Diterima);
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

    $this->actingAs($this->user)->get("/sertifikasi/permohonan/{$permohonan->ulid}")->assertOk();
    $this->actingAs($this->user)->get("/dokumen/permohonan/{$permohonan->ulid}/ijazah")->assertOk();
    $this->actingAs($this->admin)->get("/dokumen/permohonan/{$permohonan->ulid}/ktp")->assertOk();

    $this->actingAs($lain)->get("/sertifikasi/permohonan/{$permohonan->ulid}")->assertNotFound();
    $this->actingAs($lain)->get("/dokumen/permohonan/{$permohonan->ulid}/ijazah")->assertForbidden();
    $this->actingAs(User::factory()->asesor()->create())->get("/dokumen/permohonan/{$permohonan->ulid}/ktp")->assertForbidden();
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
        ->put("/pendaftaran/periode/{$this->periode->ulid}", [
            'nama' => $this->periode->nama,
            'tanggal_buka' => $this->periode->tanggal_buka->toDateString(),
            'tanggal_tutup' => $this->periode->tanggal_tutup->toDateString(),
            'kuota' => 100,
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
    $url = "/sertifikasi/permohonan/{$permohonan->ulid}/lokasi";

    // Lokasi masih ada, jadi belum perlu memilih ulang.
    $this->actingAs($this->user)->put($url, ['id_tuk' => $this->tuk->id_tuk])->assertSessionHas('error');

    $this->periode->simpanLokasi([['id_tuk' => $this->tukLain->id_tuk, 'skema' => [$this->skema->id_skema]]]);
    $this->periode->kosongkanLokasiTidakTersedia($this->admin);

    $this->actingAs($this->user)
        ->get("/sertifikasi/permohonan/{$permohonan->ulid}")
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

test('WNI is always stored as Indonesia and WNA must choose another country', function () {
    $jepang = Negara::create(['nama_negara' => 'Jepang'])->id_negara;

    // Negara yang dikirim untuk WNI diabaikan.
    $this->actingAs($this->user)->post(urlDaftar($this->periode, $this->skema), dataApl01(['kebangsaan' => 'wni', 'id_negara' => $jepang]))->assertSessionHasNoErrors();
    expect($this->dataDiri->fresh()->id_negara)->toBe($this->idIndonesia);

    $permohonan = PermohonanSertifikasi::sole();
    $permohonan->update(['status' => StatusPermohonan::Dibatalkan]);

    // Permohonan yang dibatalkan diajukan ulang lewat permohonan yang sama.
    $this->actingAs($this->user)
        ->post(route('sertifikasi.perbaiki.store', $permohonan), dataApl01(['kebangsaan' => 'wna', 'id_negara' => $this->idIndonesia]))
        ->assertSessionHasErrors('id_negara');

    $this->actingAs($this->user)->post(route('sertifikasi.perbaiki.store', $permohonan), dataApl01(['kebangsaan' => 'wna', 'id_negara' => $jepang]))->assertSessionHasNoErrors();
    expect($this->dataDiri->fresh()->id_negara)->toBe($jepang);
});

test('transcript, certificate, and portfolio are limited to 2 MB while other documents are limited to 1 MB', function () {
    $besar = fn (string $nama) => UploadedFile::fake()->create($nama, 2500, 'application/pdf');
    $sedang = fn (string $nama, string $mime = 'application/pdf') => UploadedFile::fake()->create($nama, 1500, $mime);

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'dokumen' => [
                ...dataApl01()['dokumen'],
                'transkrip' => $besar('transkrip.pdf'),
                'sertifikat_pelatihan' => $besar('sertifikat.pdf'),
                'portofolio' => $besar('portofolio.pdf'),
            ],
        ]))
        ->assertSessionHasErrors(['dokumen.transkrip', 'dokumen.sertifikat_pelatihan', 'dokumen.portofolio']);

    // 1,5 MB lolos untuk dokumen 2 MB, tetapi ditolak untuk dokumen lain.
    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'dokumen' => [
                'ktp' => $sedang('ktp.pdf'),
                'pas_foto' => $sedang('foto.png', 'image/png'),
                'npwp' => $sedang('npwp.pdf'),
                'cv' => $sedang('cv.pdf'),
                'ijazah' => $sedang('ijazah.pdf'),
                'surat_keterangan_kerja' => $sedang('surat.pdf'),
                'transkrip' => $sedang('transkrip.pdf'),
                'sertifikat_pelatihan' => $sedang('sertifikat.pdf'),
                'portofolio' => $sedang('portofolio.pdf'),
            ],
        ]))
        ->assertSessionHasErrors(['dokumen.ktp', 'dokumen.pas_foto', 'dokumen.npwp', 'dokumen.cv', 'dokumen.ijazah', 'dokumen.surat_keterangan_kerja'])
        ->assertSessionDoesntHaveErrors(['dokumen.transkrip', 'dokumen.sertifikat_pelatihan', 'dokumen.portofolio']);
});

test('the same NIK cannot be used by two accounts', function () {
    DataDiri::factory()->create(['nik' => '3578010101900001']);

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01(['nik' => '3578010101900001']))
        ->assertSessionHasErrors(['nik' => 'NIK 3578010101900001 sudah terdaftar pada akun lain. Hubungi admin LSP bila ini NIK Anda.']);

    expect(PermohonanSertifikasi::count())->toBe(0);
});

test('office region is required only when an office address is filled and is shown in the detail', function () {
    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01(['alamat_kantor' => 'Jl. Basuki Rahmat No. 5']))
        ->assertSessionHasErrors(['provinsi_kantor', 'kota_kantor', 'kecamatan_kantor', 'kelurahan_kantor']);

    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'alamat_kantor' => 'Jl. Basuki Rahmat No. 5',
            'provinsi_kantor' => 'Jawa Timur',
            'kota_kantor' => 'Kota Surabaya',
            'kecamatan_kantor' => 'Tegalsari',
            'kelurahan_kantor' => 'Kedungdoro',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->dataDiri->fresh()->kota_kantor)->toBe('Kota Surabaya');

    $this->actingAs($this->user)
        ->get(route('sertifikasi.show', PermohonanSertifikasi::sole()->ulid))
        ->assertInertia(fn ($page) => $page->where(
            'permohonan.pemohon.alamat_kantor',
            'Jl. Basuki Rahmat No. 5, Kedungdoro, Tegalsari, Kota Surabaya, Jawa Timur',
        ));
});

test('KTP and domicile addresses keep their own region', function () {
    $this->actingAs($this->user)
        ->post(urlDaftar($this->periode, $this->skema), dataApl01([
            'alamat_ktp' => 'Jl. Ijen No. 10',
            'provinsi_ktp' => 'Jawa Timur',
            'kota_ktp' => 'Kota Malang',
            'kecamatan_ktp' => 'Klojen',
            'kelurahan_ktp' => 'Oro-oro Dowo',
        ]))
        ->assertSessionHasNoErrors();

    expect($this->dataDiri->fresh())
        ->kota_ktp->toBe('Kota Malang')
        ->kota->toBe('Kota Surabaya');

    $this->actingAs($this->user)
        ->get(route('sertifikasi.show', PermohonanSertifikasi::sole()->ulid))
        ->assertInertia(fn ($page) => $page
            ->where('permohonan.pemohon.alamat_ktp', 'Jl. Ijen No. 10, Oro-oro Dowo, Klojen, Kota Malang, Jawa Timur')
            ->where('permohonan.pemohon.alamat_domisili', 'Jl. Pahlawan No. 1, Embong Kaliasin, Genteng, Kota Surabaya, Jawa Timur'));
});
