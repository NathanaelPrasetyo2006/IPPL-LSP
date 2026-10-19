<?php

namespace App\Http\Controllers\Sertifikasi;

use App\Enums\JenisDokumen;
use App\Enums\StatusPermohonan;
use App\Enums\TujuanAsesmen;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sertifikasi\PermohonanApl01Request;
use App\Models\DataDiri;
use App\Models\Negara;
use App\Models\PeriodePendaftaran;
use App\Models\PermohonanSertifikasi;
use App\Models\SkemaSertifikasi;
use App\Models\Tuk;
use App\Models\User;
use App\Notifications\PermohonanApl01Masuk;
use App\Support\PenyimpananDokumen;
use App\Support\PermohonanPresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Pendaftaran sertifikasi oleh calon asesi: pilih periode & skema, isi FR-APL-01, pantau status.
 */
class PendaftaranSertifikasiController extends Controller
{
    /**
     * Periode yang sedang dibuka beserta riwayat permohonan milik asesi.
     */
    public function index(Request $request): Response
    {
        $dataDiri = $request->user()->dataDiri;

        $permohonan = $dataDiri
            ? $dataDiri->permohonan()->with(['skema:id_skema,judul,nomor', 'periode:id_periode,nama', 'tuk:id_tuk,nama,alamat'])->latest()->get()
            : collect();

        // Skema yang sedang diproses/diterima tidak bisa didaftarkan lagi pada periode yang sama.
        $sedangAktif = $permohonan
            ->filter(fn (PermohonanSertifikasi $p) => in_array($p->status->value, StatusPermohonan::aktif(), true))
            ->map(fn (PermohonanSertifikasi $p) => "{$p->id_periode}-{$p->id_skema}")
            ->all();

        $periode = PeriodePendaftaran::query()
            ->dibuka()
            ->with(['skema' => fn ($query) => $query->withCount('unitKompetensi')->orderBy('nomor')])
            ->orderBy('tanggal_tutup')
            ->get()
            ->map(fn (PeriodePendaftaran $periode) => [
                'id_periode' => $periode->id_periode,
                'nama' => $periode->nama,
                'keterangan' => $periode->keterangan,
                'tanggal_buka' => $periode->tanggal_buka->locale('id')->translatedFormat('d M Y'),
                'tanggal_tutup' => $periode->tanggal_tutup->locale('id')->translatedFormat('d M Y'),
                'kuota_penuh' => $periode->kuotaPenuh(),
                'skema' => $periode->skema->map(fn (SkemaSertifikasi $skema) => [
                    'id_skema' => $skema->id_skema,
                    'judul' => $skema->judul,
                    'nomor' => $skema->nomor,
                    'unit_kompetensi_count' => $skema->unit_kompetensi_count,
                    'sudah_mendaftar' => in_array("{$periode->id_periode}-{$skema->id_skema}", $sedangAktif, true),
                ])->values(),
            ]);

        return Inertia::render('sertifikasi/index', [
            'kodeAsesi' => $dataDiri?->kode_asesi,
            'periode' => $periode,
            'permohonan' => $permohonan->map(fn (PermohonanSertifikasi $p) => PermohonanPresenter::ringkas($p))->values(),
        ]);
    }

    /**
     * Formulir FR-APL-01 untuk skema terpilih.
     */
    public function create(Request $request, PeriodePendaftaran $periode, SkemaSertifikasi $skema): Response|RedirectResponse
    {
        if ($alasan = $this->alasanTidakBisaDaftar($request->user(), $periode, $skema)) {
            return to_route('sertifikasi.index')->with('error', $alasan);
        }

        $dataDiri = $request->user()->dataDiri;

        $profil = collect(PermohonanApl01Request::KOLOM_DATA_DIRI)
            ->mapWithKeys(fn (string $kolom) => [$kolom => $dataDiri?->{$kolom}])
            ->all();

        $profil['tgl_lahir'] = $dataDiri?->tgl_lahir?->toDateString();
        $profil['id_negara'] ??= Negara::query()->where('nama_negara', 'Indonesia')->value('id_negara');

        return Inertia::render('sertifikasi/create', [
            'periode' => [
                'id_periode' => $periode->id_periode,
                'nama' => $periode->nama,
                'tanggal_tutup' => $periode->tanggal_tutup->locale('id')->translatedFormat('d F Y'),
            ],
            'skema' => [
                'id_skema' => $skema->id_skema,
                'judul' => $skema->judul,
                'nomor' => $skema->nomor,
                'unit_kompetensi' => $skema->unitKompetensi()->orderBy('urutan')->orderBy('kode')->get(['id_unit_kompetensi', 'kode', 'judul']),
            ],
            'lokasi' => $this->lokasiOptions($periode, $skema->id_skema),
            'kodeAsesi' => $dataDiri?->kode_asesi,
            'email' => $request->user()->email,
            'profil' => $profil,
            'dokumenIdentitas' => collect(JenisDokumen::cases())
                ->filter->identitas()
                ->mapWithKeys(fn (JenisDokumen $jenis) => [
                    $jenis->value => filled($dataDiri?->{$jenis->kolomDataDiri()})
                        ? route('sertifikasi.dokumen-saya', $jenis->value)
                        : null,
                ]),
            'options' => [
                'negara' => Negara::query()->orderByRaw("case when nama_negara = 'Indonesia' then 0 else 1 end")->orderBy('nama_negara')->pluck('nama_negara', 'id_negara'),
                'jenis_kelamin' => DataDiri::JENIS_KELAMIN,
                'pendidikan' => DataDiri::PENDIDIKAN,
                'tujuan_asesmen' => TujuanAsesmen::options(),
                'dokumen' => JenisDokumen::definisi(),
            ],
        ]);
    }

    /**
     * Simpan FR-APL-01: perbarui profil, simpan dokumen ke folder asesi, buat permohonan.
     */
    public function store(PermohonanApl01Request $request, PeriodePendaftaran $periode, SkemaSertifikasi $skema): RedirectResponse
    {
        $user = $request->user();

        if ($alasan = $this->alasanTidakBisaDaftar($user, $periode, $skema)) {
            return to_route('sertifikasi.index')->with('error', $alasan);
        }

        $data = $request->validated();
        $fileBaru = [];
        $fileLama = [];

        try {
            $permohonan = DB::transaction(function () use ($user, $data, $request, $periode, $skema, &$fileBaru, &$fileLama) {
                $dataDiri = $user->dataDiri ?? new DataDiri(['id_user' => $user->id_user]);
                $dataDiri->fill(Arr::only($data, PermohonanApl01Request::KOLOM_DATA_DIRI))->save();

                $folder = $dataDiri->folderDokumen();

                // Dokumen identitas menggantikan file lama di profil asesi.
                foreach (JenisDokumen::cases() as $jenis) {
                    if (! $jenis->identitas() || ! $request->hasFile("dokumen.{$jenis->value}")) {
                        continue;
                    }

                    $kolom = $jenis->kolomDataDiri();
                    $fileLama[] = $dataDiri->{$kolom};
                    $dataDiri->{$kolom} = $fileBaru[] = PenyimpananDokumen::simpan($request->file("dokumen.{$jenis->value}"), "{$folder}/identitas", $jenis);
                }

                $dataDiri->save();

                $permohonan = PermohonanSertifikasi::create([
                    'id_data_diri' => $dataDiri->id_data_diri,
                    'id_periode' => $periode->id_periode,
                    'id_skema' => $skema->id_skema,
                    'tujuan_asesmen' => $data['tujuan_asesmen'],
                    'tujuan_asesmen_lainnya' => $data['tujuan_asesmen'] === TujuanAsesmen::Lainnya->value ? $data['tujuan_asesmen_lainnya'] : null,
                    'status' => StatusPermohonan::Diajukan,
                ]);

                $permohonan->gantiLokasi((int) $data['id_tuk'], 'Dipilih saat pendaftaran.', $user);
                $permohonan->setRelation('dataDiri', $dataDiri);

                // Bukti persyaratan dasar disimpan di folder milik permohonan ini.
                foreach (JenisDokumen::cases() as $jenis) {
                    if ($jenis->identitas() || ! $request->hasFile("dokumen.{$jenis->value}")) {
                        continue;
                    }

                    $file = $request->file("dokumen.{$jenis->value}");

                    $permohonan->dokumen()->create([
                        'jenis' => $jenis,
                        'nama_asli' => mb_substr($file->getClientOriginalName(), 0, 255),
                        'path' => $fileBaru[] = PenyimpananDokumen::simpan($file, $permohonan->folderDokumen(), $jenis),
                        'mime' => $file->getMimeType() ?? 'application/octet-stream',
                        'ukuran' => $file->getSize(),
                    ]);
                }

                return $permohonan;
            });
        } catch (Throwable $e) {
            // Data batal tersimpan, jadi file yang sempat ditulis ikut dibersihkan.
            PenyimpananDokumen::hapus($fileBaru);

            throw $e;
        }

        PenyimpananDokumen::hapus($fileLama);

        $permohonan->load(['skema', 'dataDiri']);
        Notification::send(User::query()->where('role', UserRole::Admin)->get(), new PermohonanApl01Masuk($permohonan));

        return to_route('sertifikasi.show', $permohonan->id_permohonan)
            ->with('success', "Permohonan {$permohonan->nomor} berhasil diajukan. Hasil verifikasi akan dikirim ke email Anda.");
    }

    /**
     * Detail dan status permohonan milik asesi.
     */
    public function show(Request $request, PermohonanSertifikasi $permohonan): Response
    {
        abort_unless($permohonan->dataDiri?->id_user === $request->user()->id_user, 404);

        $detail = PermohonanPresenter::detail($permohonan);

        return Inertia::render('sertifikasi/show', [
            'permohonan' => $detail,
            'lokasiTersedia' => $permohonan->perluPilihLokasi() ? $this->lokasiOptions($permohonan->periode, $permohonan->id_skema) : [],
            // Asesi yang ditolak bisa langsung mendaftar ulang bila periode masih dibuka.
            'bisaDaftarUlang' => $permohonan->status === StatusPermohonan::Ditolak
                && $this->alasanTidakBisaDaftar($request->user(), $permohonan->periode, $permohonan->skema) === null,
        ]);
    }

    /**
     * Asesi memilih ulang lokasi uji setelah lokasi sebelumnya dihapus admin.
     */
    public function pilihLokasi(Request $request, PermohonanSertifikasi $permohonan): RedirectResponse
    {
        abort_unless($permohonan->dataDiri?->id_user === $request->user()->id_user, 404);

        if (! $permohonan->perluPilihLokasi()) {
            return back()->with('error', 'Lokasi uji permohonan ini tidak perlu dipilih ulang.');
        }

        $data = $request->validate(
            ['id_tuk' => ['required', 'integer', Rule::in($permohonan->periode->lokasiSkema($permohonan->id_skema)->pluck('id_tuk')->all())]],
            ['id_tuk.required' => 'Pilih lokasi uji.', 'id_tuk.in' => 'Lokasi uji yang dipilih tidak tersedia untuk skema ini.'],
        );

        $permohonan->gantiLokasi((int) $data['id_tuk'], 'Dipilih ulang oleh asesi.', $request->user());

        return back()->with('success', 'Lokasi uji berhasil disimpan.');
    }

    /**
     * Dokumen identitas milik asesi sendiri (dipakai di form APL-01).
     */
    public function dokumenSaya(Request $request, string $jenis): StreamedResponse
    {
        $jenis = JenisDokumen::tryFrom($jenis);
        $path = $jenis?->identitas() ? $request->user()->dataDiri?->{$jenis->kolomDataDiri()} : null;

        abort_unless($path, 404);

        return PenyimpananDokumen::tampilkan($path, $jenis->value.'.'.pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * @return list<array{id_tuk: int, nama: string, alamat: string}>
     */
    private function lokasiOptions(PeriodePendaftaran $periode, int $idSkema): array
    {
        return $periode->lokasiSkema($idSkema)
            ->map(fn (Tuk $tuk) => $tuk->only(['id_tuk', 'nama', 'alamat']))
            ->values()
            ->all();
    }

    /**
     * Pesan alasan bila asesi tidak bisa mendaftar skema ini, atau null bila boleh.
     */
    private function alasanTidakBisaDaftar(User $user, PeriodePendaftaran $periode, SkemaSertifikasi $skema): ?string
    {
        if ($periode->status() !== 'dibuka') {
            return "Periode {$periode->nama} sedang tidak menerima pendaftaran.";
        }

        if (! $periode->skema()->whereKey($skema->id_skema)->exists()) {
            return "Skema {$skema->judul} tidak dibuka pada periode {$periode->nama}.";
        }

        $sudahAktif = $user->dataDiri?->permohonan()
            ->where('id_periode', $periode->id_periode)
            ->where('id_skema', $skema->id_skema)
            ->whereIn('status', StatusPermohonan::aktif())
            ->exists();

        if ($sudahAktif) {
            return "Anda sudah mendaftar skema {$skema->judul} pada periode ini.";
        }

        if ($periode->kuotaPenuh()) {
            return "Kuota pendaftar periode {$periode->nama} sudah penuh.";
        }

        return null;
    }
}
