<?php

namespace App\Http\Controllers\Pendaftaran;

use App\Enums\StatusPermohonan;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pendaftaran\PeriodePendaftaranRequest;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\Tuk;
use App\Notifications\LokasiPermohonanPerluDipilihUlang;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class PeriodePendaftaranController extends Controller
{
    /**
     * Daftar periode pendaftaran sertifikasi.
     */
    public function index(): Response
    {
        $periode = PeriodePendaftaran::query()
            ->with('skema:id_skema,judul,nomor')
            ->withCount([
                'permohonan',
                'permohonan as permohonan_menunggu_count' => fn ($query) => $query->where('status', StatusPermohonan::Diajukan),
                'permohonan as kuota_terpakai_count' => fn ($query) => $query->whereIn('status', StatusPermohonan::aktif()),
            ])
            ->orderByDesc('tanggal_buka')
            ->paginate(10)
            ->through(fn (PeriodePendaftaran $periode) => [
                'id_periode' => $periode->id_periode,
                'nama' => $periode->nama,
                'tanggal_buka' => $periode->tanggal_buka->locale('id')->translatedFormat('d M Y'),
                'tanggal_tutup' => $periode->tanggal_tutup->locale('id')->translatedFormat('d M Y'),
                'kuota' => $periode->kuota,
                'kuota_terpakai' => $periode->kuota_terpakai_count,
                'aktif' => $periode->aktif,
                'status' => $periode->status(),
                'skema' => $periode->skema->map->only(['id_skema', 'judul', 'nomor'])->values(),
                'jumlah_permohonan' => $periode->permohonan_count,
                'permohonan_menunggu' => $periode->permohonan_menunggu_count,
            ]);

        return Inertia::render('pendaftaran/periode/index', [
            'periode' => $periode,
            'stats' => [
                'total_periode' => PeriodePendaftaran::count(),
                'periode_dibuka' => PeriodePendaftaran::query()->dibuka()->count(),
                'menunggu_verifikasi' => PermohonanSertifikasi::query()->where('status', StatusPermohonan::Diajukan)->count(),
            ],
        ]);
    }

    /**
     * Form buka periode baru.
     */
    public function create(): Response
    {
        return Inertia::render('pendaftaran/periode/create', [
            'skemaOptions' => $this->skemaOptions(),
            'tukOptions' => $this->tukOptions(),
        ]);
    }

    /**
     * Simpan periode baru beserta skema yang dibuka dan lokasinya.
     */
    public function store(PeriodePendaftaranRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $periode = PeriodePendaftaran::create(Arr::except($data, ['skema', 'lokasi']));
            $periode->skema()->sync($data['skema']);
            $periode->simpanLokasi($data['lokasi']);
        });

        return to_route('pendaftaran.periode.index')
            ->with('success', "Periode {$data['nama']} berhasil dibuat.");
    }

    /**
     * Form ubah periode.
     */
    public function edit(PeriodePendaftaran $periode): Response
    {
        return Inertia::render('pendaftaran/periode/edit', [
            'periode' => [
                'id_periode' => $periode->id_periode,
                'nama' => $periode->nama,
                'tanggal_buka' => $periode->tanggal_buka->toDateString(),
                'tanggal_tutup' => $periode->tanggal_tutup->toDateString(),
                'kuota' => $periode->kuota,
                'keterangan' => $periode->keterangan,
                'aktif' => $periode->aktif,
                'skema' => $periode->skema()->pluck('skema_sertifikasi.id_skema'),
                'lokasi' => $periode->lokasiForm(),
            ],
            'skemaTerpakai' => $this->skemaTerpakai($periode),
            // Jumlah pendaftar berjalan per "id_tuk-id_skema", supaya admin tahu dampak melepas lokasi.
            'pendaftarLokasi' => (object) $periode->permohonan()
                ->whereIn('status', StatusPermohonan::aktif())
                ->whereNotNull('id_tuk')
                ->selectRaw('id_tuk, id_skema, count(*) as jumlah')
                ->groupBy('id_tuk', 'id_skema')
                ->get()
                ->mapWithKeys(fn ($baris) => ["{$baris->id_tuk}-{$baris->id_skema}" => (int) $baris->jumlah])
                ->all(),
            'skemaOptions' => $this->skemaOptions(),
            'tukOptions' => $this->tukOptions(),
        ]);
    }

    /**
     * Perbarui periode, daftar skema, dan lokasinya.
     */
    public function update(PeriodePendaftaranRequest $request, PeriodePendaftaran $periode): RedirectResponse
    {
        $data = $request->validated();

        // Skema yang sudah punya pendaftar tidak boleh dilepas dari periode.
        $dilepas = array_diff($this->skemaTerpakai($periode), $data['skema']);

        if ($dilepas !== []) {
            throw ValidationException::withMessages([
                'skema' => 'Skema yang sudah memiliki pendaftar pada periode ini tidak dapat dihapus dari daftar.',
            ]);
        }

        $terdampak = DB::transaction(function () use ($data, $periode, $request) {
            $periode->update(Arr::except($data, ['skema', 'lokasi']));
            $periode->skema()->sync($data['skema']);
            $periode->simpanLokasi($data['lokasi']);

            return $periode->kosongkanLokasiTidakTersedia($request->user());
        });

        // Dikirim setelah commit supaya asesi tidak menerima pemberitahuan untuk perubahan yang batal.
        foreach ($terdampak as ['permohonan' => $permohonan, 'lokasi_lama' => $lokasiLama]) {
            $permohonan->loadMissing(['dataDiri.user', 'skema', 'periode']);
            $permohonan->dataDiri->user?->notify(new LokasiPermohonanPerluDipilihUlang($permohonan, $lokasiLama));
        }

        $pesan = "Periode {$periode->nama} berhasil diperbarui.";

        if ($terdampak !== []) {
            $pesan .= ' '.count($terdampak).' pendaftar diminta memilih ulang lokasi uji karena lokasinya dihapus.';
        }

        return to_route('pendaftaran.periode.index')->with('success', $pesan);
    }

    /**
     * Tutup periode lebih awal atau buka kembali.
     */
    public function toggle(PeriodePendaftaran $periode): RedirectResponse
    {
        $periode->update(['aktif' => ! $periode->aktif]);

        return back()->with('success', $periode->aktif
            ? "Periode {$periode->nama} dibuka kembali."
            : "Periode {$periode->nama} ditutup. Calon asesi tidak dapat mendaftar lagi.");
    }

    /**
     * Hapus periode yang belum memiliki pendaftar.
     */
    public function destroy(PeriodePendaftaran $periode): RedirectResponse
    {
        if ($periode->permohonan()->exists()) {
            return back()->with('error', "Periode {$periode->nama} sudah memiliki pendaftar dan tidak dapat dihapus. Tutup periode bila tidak ingin menerima pendaftaran lagi.");
        }

        $periode->delete();

        return to_route('pendaftaran.periode.index')
            ->with('success', 'Periode pendaftaran berhasil dihapus.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function skemaOptions(): array
    {
        return SkemaSertifikasi::query()
            ->withCount('unitKompetensi')
            ->orderBy('nomor')
            ->get(['id_skema', 'judul', 'nomor'])
            ->map(fn (SkemaSertifikasi $skema) => [
                'id_skema' => $skema->id_skema,
                'judul' => $skema->judul,
                'nomor' => $skema->nomor,
                'unit_kompetensi_count' => $skema->unit_kompetensi_count,
            ])
            ->all();
    }

    /**
     * @return list<array{id_tuk: int, nama: string, alamat: string}>
     */
    private function tukOptions(): array
    {
        return Tuk::query()->orderBy('nama')->get(['id_tuk', 'nama', 'alamat'])->toArray();
    }

    /**
     * Id skema yang sudah dipakai permohonan pada periode ini.
     *
     * @return list<int>
     */
    private function skemaTerpakai(PeriodePendaftaran $periode): array
    {
        return $periode->permohonan()->distinct()->pluck('id_skema')->map(fn ($id) => (int) $id)->all();
    }
}
