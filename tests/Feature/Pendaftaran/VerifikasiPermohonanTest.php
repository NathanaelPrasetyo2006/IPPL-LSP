<?php

use App\Enums\StatusPermohonan;
use App\Models\DokumenPermohonan;
use App\Models\PermohonanSertifikasi;
use App\Models\User;
use App\Notifications\HasilVerifikasiApl01;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();

    // Permohonan dengan semua dokumen wajib (KTP & pas foto di profil, ijazah di permohonan).
    $this->permohonan = PermohonanSertifikasi::factory()->create();
    $this->permohonan->dataDiri->update(['file_ktp' => 'asesi/x/identitas/ktp.jpg', 'file_pas_foto' => 'asesi/x/identitas/foto.png']);

    DokumenPermohonan::create([
        'id_permohonan' => $this->permohonan->id_permohonan,
        'jenis' => 'ijazah',
        'nama_asli' => 'ijazah.pdf',
        'path' => 'asesi/x/apl-01/ijazah.pdf',
        'mime' => 'application/pdf',
        'ukuran' => 1024,
    ]);

    $this->url = "/pendaftaran/verifikasi/{$this->permohonan->id_permohonan}";
    $this->asesi = $this->permohonan->dataDiri->user;
});

const SEMUA_MEMENUHI = ['ktp' => true, 'pas_foto' => true, 'ijazah' => true];

test('verifier dashboard lists waiting applications', function () {
    PermohonanSertifikasi::factory()->create(['status' => StatusPermohonan::Diterima]);

    $this->actingAs($this->admin)
        ->get('/pendaftaran/verifikasi')
        ->assertInertia(fn ($page) => $page
            ->component('pendaftaran/verifikasi/index')
            ->has('permohonan.data', 1)
            ->where('stats.diajukan', 1)
            ->where('stats.diterima', 1));

    $this->actingAs($this->admin)
        ->get($this->url)
        ->assertInertia(fn ($page) => $page
            ->component('pendaftaran/verifikasi/show')
            ->where('permohonan.dokumen.0.jenis', 'ktp')
            ->where('permohonan.dokumen.0.ada', true));
});

test('accepting notifies the asesi by email and in the app', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'diterima', 'catatan' => 'Lanjut ke jadwal asesmen.', 'cek_dokumen' => SEMUA_MEMENUHI])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success');

    $permohonan = $this->permohonan->fresh();

    expect($permohonan->status)->toBe(StatusPermohonan::Diterima)
        ->and($permohonan->id_verifikator)->toBe($this->admin->id_user)
        ->and($permohonan->cek_dokumen)->toBe(SEMUA_MEMENUHI)
        ->and($permohonan->diverifikasi_pada)->not->toBeNull();

    Notification::assertSentTo($this->asesi, HasilVerifikasiApl01::class, function ($notification, array $channels) {
        return $channels === ['database', 'mail'];
    });
});

test('cannot accept while a required document is not marked as fulfilled', function () {
    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'diterima', 'cek_dokumen' => ['ktp' => true, 'pas_foto' => true, 'ijazah' => false]])
        ->assertSessionHasErrors('keputusan');

    expect($this->permohonan->fresh()->status)->toBe(StatusPermohonan::Diajukan);
});

test('rejecting requires a note for the asesi', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'ditolak', 'catatan' => '', 'cek_dokumen' => []])
        ->assertSessionHasErrors('catatan');

    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'ditolak', 'catatan' => 'Ijazah tidak terbaca.', 'cek_dokumen' => ['ijazah' => false]])
        ->assertSessionHasNoErrors();

    expect($this->permohonan->fresh())
        ->status->toBe(StatusPermohonan::Ditolak)
        ->catatan_verifikasi->toBe('Ijazah tidak terbaca.');

    Notification::assertSentTo($this->asesi, HasilVerifikasiApl01::class);
});

test('a decided application cannot be decided again', function () {
    $this->permohonan->update(['status' => StatusPermohonan::Ditolak]);

    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'diterima', 'cek_dokumen' => SEMUA_MEMENUHI])
        ->assertSessionHasErrors('keputusan');

    expect($this->permohonan->fresh()->status)->toBe(StatusPermohonan::Ditolak);
});

test('in-app notification shows in the bell and opens the application', function () {
    $this->actingAs($this->admin)
        ->put($this->url, ['keputusan' => 'ditolak', 'catatan' => 'Ijazah tidak terbaca.', 'cek_dokumen' => []]);

    $notifikasi = $this->asesi->notifications()->sole();

    $this->actingAs($this->asesi)
        ->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->where('notifikasi.belum_dibaca', 1)
            ->where('notifikasi.terbaru.0.judul', 'Permohonan ditolak'));

    $this->actingAs($this->asesi)
        ->post("/notifikasi/{$notifikasi->id}/baca")
        ->assertRedirect("/sertifikasi/permohonan/{$this->permohonan->id_permohonan}");

    expect($notifikasi->fresh()->read_at)->not->toBeNull();
});

test('only admin can verify applications', function () {
    $this->actingAs($this->asesi)->get('/pendaftaran/verifikasi')->assertForbidden();
    $this->actingAs($this->asesi)->put($this->url, ['keputusan' => 'diterima', 'cek_dokumen' => SEMUA_MEMENUHI])->assertForbidden();
});
