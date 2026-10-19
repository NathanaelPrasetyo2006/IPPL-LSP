<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pendaftaran\VerifikasiPermohonanRequest;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Notifications\HasilVerifikasiApl01;
use App\Support\PermohonanPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

/**
 * Dashboard verifikator (admin LSP): cek kelengkapan berkas APL-01 dan putuskan diterima/ditolak.
 */
class VerifikasiPermohonanController extends Controller
{
    public function index(Request $request): Response
    {
        $status = StatusPermohonan::tryFrom((string) $request->query('status', 'diajukan'));
        // Filter periode memakai kode acak (ulid), bukan id, supaya id tidak tampil di URL.
        $periodeDipilih = PeriodePendaftaran::query()->where('ulid', (string) $request->query('periode'))->first();
        $idPeriode = $periodeDipilih?->id_periode;
        $search = trim((string) $request->query('search', ''));

        $permohonan = PermohonanSertifikasi::query()
            ->with(['dataDiri:id_data_diri,nama_lengkap,kode_asesi', 'skema:id_skema,judul,nomor', 'periode:id_periode,ulid,nama'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($idPeriode, fn ($query) => $query->where('id_periode', $idPeriode))
            ->when($search !== '', function ($query) use ($search) {
                // lower() dipakai supaya pencarian tetap case-insensitive di PostgreSQL.
                $keyword = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('lower(nomor) like ?', [$keyword])
                        ->orWhereHas('dataDiri', fn ($query) => $query
                            ->whereRaw('lower(nama_lengkap) like ?', [$keyword])
                            ->orWhereRaw('lower(kode_asesi) like ?', [$keyword]));
                });
            })
            // Yang menunggu paling lama diverifikasi lebih dulu.
            ->orderBy($status === StatusPermohonan::Diajukan ? 'created_at' : 'updated_at', $status === StatusPermohonan::Diajukan ? 'asc' : 'desc')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (PermohonanSertifikasi $p) => PermohonanPresenter::ringkas($p));

        $hitung = PermohonanSertifikasi::query()
            ->when($idPeriode, fn ($query) => $query->where('id_periode', $idPeriode))
            ->selectRaw('status, count(*) as jumlah')
            ->groupBy('status')
            ->pluck('jumlah', 'status');

        return Inertia::render('pendaftaran/verifikasi/index', [
            'permohonan' => $permohonan,
            'filters' => [
                'status' => $status?->value ?? 'semua',
                'periode' => $periodeDipilih?->ulid,
                'search' => $search,
            ],
            'stats' => [
                'diajukan' => (int) ($hitung[StatusPermohonan::Diajukan->value] ?? 0),
                'diterima' => (int) ($hitung[StatusPermohonan::Diterima->value] ?? 0),
                'ditolak' => (int) ($hitung[StatusPermohonan::Ditolak->value] ?? 0),
                'dibatalkan' => (int) ($hitung[StatusPermohonan::Dibatalkan->value] ?? 0),
            ],
            'periodeOptions' => PeriodePendaftaran::query()->orderByDesc('tanggal_buka')->pluck('nama', 'ulid'),
        ]);
    }

    public function show(PermohonanSertifikasi $permohonan): Response
    {
        return Inertia::render('pendaftaran/verifikasi/show', [
            'permohonan' => PermohonanPresenter::detail($permohonan),
            'jenisDokumen' => JenisDokumen::definisi(),
        ]);
    }

    /**
     * Simpan keputusan lalu kirim notifikasi otomatis ke asesi (di aplikasi + email).
     */
    public function update(VerifikasiPermohonanRequest $request, PermohonanSertifikasi $permohonan): RedirectResponse
    {
        $data = $request->validated();

        $permohonan->update([
            'status' => $data['keputusan'],
            'catatan_verifikasi' => $data['catatan'] ?? null,
            'cek_dokumen' => collect($data['cek_dokumen'] ?? [])->map(fn ($nilai) => (bool) $nilai)->all(),
            'id_verifikator' => $request->user()->id_user,
            'diverifikasi_pada' => now(),
        ]);

        $permohonan->catatStatus($permohonan->status, $data['catatan'] ?? null, $request->user());

        $permohonan->load(['dataDiri.user', 'skema', 'periode']);
        $label = $permohonan->status->label();

        try {
            $permohonan->dataDiri->user->notify(new HasilVerifikasiApl01($permohonan));
        } catch (Throwable $e) {
            // Keputusan tetap sah; notifikasi di aplikasi sudah tersimpan sebelum email dicoba.
            report($e);

            return to_route('pendaftaran.verifikasi.show', $permohonan)
                ->with('error', "Permohonan {$permohonan->nomor} ditandai {$label}, tetapi email notifikasi gagal dikirim. Periksa pengaturan email (MAIL_*) di .env.");
        }

        return to_route('pendaftaran.verifikasi.show', $permohonan)
            ->with('success', "Permohonan {$permohonan->nomor} ditandai {$label}. Notifikasi sudah dikirim ke {$permohonan->dataDiri->user->email}.");
    }
}
