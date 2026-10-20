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
 * Pendaftaran sertifikasi oleh calon asesi: pilih periode & skema, isi FR-APL-01, pantau status,
 * batalkan bila salah pilih skema, atau perbaiki permohonan yang ditolak.
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
            ? $dataDiri->permohonan()->with(['skema:id_skema,ulid,judul,nomor', 'periode:id_periode,ulid,nama', 'tuk:id_tuk,ulid,nama,alamat,kelurahan,kecamatan,kota,provinsi'])->latest()->get()
            : collect();

        $periode = PeriodePendaftaran::query()
            ->dibuka()
            ->with(['skema' => fn ($query) => $query->withCount('unitKompetensi')->orderBy('nomor')])
            ->orderBy('tanggal_tutup')
            ->get()
            ->map(function (PeriodePendaftaran $periode) use ($permohonan) {
                $milikPeriode = $permohonan->where('id_periode', $periode->id_periode);

                // Asesi hanya boleh punya satu permohonan aktif per periode.
                $aktif = $milikPeriode->first(fn (PermohonanSertifikasi $p) => in_array($p->status->value, StatusPermohonan::aktif(), true));

                return [
                    'id_periode' => $periode->id_periode,
                    'ulid' => $periode->ulid,
                    'nama' => $periode->nama,
                    'keterangan' => $periode->keterangan,
                    'tanggal_buka' => $periode->tanggal_buka->locale('id')->translatedFormat('d M Y'),
                    'tanggal_tutup' => $periode->tanggal_tutup->locale('id')->translatedFormat('d M Y'),
                    'kuota_penuh' => $periode->kuotaPenuh(),
                    'skema_terdaftar' => $aktif?->skema?->judul,
                    'skema' => $periode->skema->map(function (SkemaSertifikasi $skema) use ($aktif, $milikPeriode) {
                        $ditolak = $milikPeriode->first(fn (PermohonanSertifikasi $p) => $p->id_skema === $skema->id_skema && $p->status === StatusPermohonan::Ditolak);

                        return [
                            'id_skema' => $skema->id_skema,
                            'ulid' => $skema->ulid,
                            'judul' => $skema->judul,
                            'nomor' => $skema->nomor,
                            'unit_kompetensi_count' => $skema->unit_kompetensi_count,
                            // terdaftar | terkunci (sudah daftar skema lain) | perbaiki (pernah ditolak) | daftar
                            'keadaan' => match (true) {
                                $aktif?->id_skema === $skema->id_skema => 'terdaftar',
                                $aktif !== null => 'terkunci',
                                $ditolak !== null => 'perbaiki',
                                default => 'daftar',
                            },
                            'permohonan_ulid' => ($aktif?->id_skema === $skema->id_skema ? $aktif : $ditolak)?->ulid,
                        ];
                    })->values(),
                ];
            });

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
        // Skema yang pernah ditolak pada periode ini diperbaiki, bukan didaftarkan ulang sebagai permohonan baru.
        $ditolak = $this->permohonanDitolak($request->user(), $periode, $skema);

        if ($ditolak) {
            return to_route('sertifikasi.perbaiki', $ditolak);
        }

        if ($alasan = $this->alasanTidakBisaDaftar($request->user(), $periode, $skema)) {
            return to_route('sertifikasi.index')->with('error', $alasan);
        }

        return Inertia::render('sertifikasi/create', $this->propsFormulir($request, $periode, $skema, null));
    }

    /**
     * Simpan FR-APL-01: perbarui profil, simpan dokumen ke folder asesi, buat permohonan.
     */
    public function store(PermohonanApl01Request $request, PeriodePendaftaran $periode, SkemaSertifikasi $skema): RedirectResponse
    {
        if ($this->permohonanDitolak($request->user(), $periode, $skema) || ($alasan = $this->alasanTidakBisaDaftar($request->user(), $periode, $skema))) {
            return to_route('sertifikasi.index')->with('error', $alasan ?? 'Perbaiki permohonan yang ditolak untuk skema ini.');
        }

        $permohonan = $this->simpanApl01($request, $periode, $skema, null);

        return to_route('sertifikasi.show', $permohonan)
            ->with('success', "Permohonan {$permohonan->nomor} berhasil diajukan. Hasil verifikasi akan dikirim ke email Anda.");
    }

    /**
     * Formulir FR-APL-01 terisi untuk memperbaiki permohonan yang ditolak.
     */
    public function perbaiki(Request $request, PermohonanSertifikasi $permohonan): Response|RedirectResponse
    {
        $this->pastikanPemilik($request, $permohonan);

        if ($alasan = $this->alasanTidakBisaPerbaiki($permohonan)) {
            return to_route('sertifikasi.show', $permohonan)->with('error', $alasan);
        }

        return Inertia::render('sertifikasi/create', $this->propsFormulir($request, $permohonan->periode, $permohonan->skema, $permohonan));
    }

    /**
     * Ajukan ulang permohonan yang ditolak: nomor dan folder tetap, dokumen yang tidak diganti tetap dipakai.
     */
    public function simpanPerbaikan(PermohonanApl01Request $request, PermohonanSertifikasi $permohonan): RedirectResponse
    {
        $this->pastikanPemilik($request, $permohonan);

        if ($alasan = $this->alasanTidakBisaPerbaiki($permohonan)) {
            return to_route('sertifikasi.show', $permohonan)->with('error', $alasan);
        }

        $this->simpanApl01($request, $permohonan->periode, $permohonan->skema, $permohonan);

        return to_route('sertifikasi.show', $permohonan)
            ->with('success', "Permohonan {$permohonan->nomor} berhasil diajukan ulang. Hasil verifikasi akan dikirim ke email Anda.");
    }

    /**
     * Asesi membatalkan permohonannya sendiri sebelum diverifikasi, mis. karena salah memilih skema.
     */
    public function batal(Request $request, PermohonanSertifikasi $permohonan): RedirectResponse
    {
        $this->pastikanPemilik($request, $permohonan);

        if ($permohonan->status !== StatusPermohonan::Diajukan) {
            return back()->with('error', 'Hanya permohonan yang masih menunggu verifikasi yang bisa dibatalkan.');
        }

        DB::transaction(function () use ($permohonan, $request) {
            $permohonan->update(['status' => StatusPermohonan::Dibatalkan]);
            $permohonan->catatStatus(StatusPermohonan::Dibatalkan, 'Dibatalkan oleh asesi.', $request->user());
        });

        return to_route('sertifikasi.index')
            ->with('success', "Permohonan {$permohonan->nomor} dibatalkan. Anda bisa mendaftar skema lain selama periode masih dibuka.");
    }

    /**
     * Detail dan status permohonan milik asesi.
     */
    public function show(Request $request, PermohonanSertifikasi $permohonan): Response
    {
        $this->pastikanPemilik($request, $permohonan);

        return Inertia::render('sertifikasi/show', [
            'permohonan' => PermohonanPresenter::detail($permohonan),
            'lokasiTersedia' => $permohonan->perluPilihLokasi() ? $this->lokasiOptions($permohonan->periode, $permohonan->id_skema) : [],
            // Permohonan yang ditolak bisa diperbaiki dan diajukan lagi selama periode masih dibuka.
            'bisaPerbaiki' => $permohonan->status === StatusPermohonan::Ditolak && $this->alasanTidakBisaPerbaiki($permohonan) === null,
            'bisaBatal' => $permohonan->status === StatusPermohonan::Diajukan,
        ]);
    }

    /**
     * Asesi memilih ulang lokasi uji setelah lokasi sebelumnya dihapus admin.
     */
    public function pilihLokasi(Request $request, PermohonanSertifikasi $permohonan): RedirectResponse
    {
        $this->pastikanPemilik($request, $permohonan);

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
     * Props formulir APL-01, untuk pendaftaran baru maupun perbaikan.
     *
     * @return array<string, mixed>
     */
    private function propsFormulir(Request $request, PeriodePendaftaran $periode, SkemaSertifikasi $skema, ?PermohonanSertifikasi $permohonan): array
    {
        $dataDiri = $request->user()->dataDiri;

        $profil = collect(PermohonanApl01Request::KOLOM_DATA_DIRI)
            ->mapWithKeys(fn (string $kolom) => [$kolom => $dataDiri?->{$kolom}])
            ->all();

        $profil['tgl_lahir'] = $dataDiri?->tgl_lahir?->toDateString();
        $idIndonesia = PermohonanApl01Request::idIndonesia();
        $profil['id_negara'] ??= $idIndonesia;

        $dokumenPermohonan = $permohonan?->dokumen->keyBy(fn ($dokumen) => $dokumen->jenis->value);

        return [
            'periode' => [
                'id_periode' => $periode->id_periode,
                'ulid' => $periode->ulid,
                'nama' => $periode->nama,
                'tanggal_tutup' => $periode->tanggal_tutup->locale('id')->translatedFormat('d F Y'),
            ],
            'skema' => [
                'id_skema' => $skema->id_skema,
                'ulid' => $skema->ulid,
                'judul' => $skema->judul,
                'nomor' => $skema->nomor,
                'unit_kompetensi' => $skema->unitKompetensi()->orderBy('urutan')->orderBy('kode')->get(['id_unit_kompetensi', 'kode', 'judul']),
            ],
            'lokasi' => $this->lokasiOptions($periode, $skema->id_skema),
            'kodeAsesi' => $dataDiri?->kode_asesi,
            'email' => $request->user()->email,
            'profil' => $profil,
            // Mode perbaiki: permohonan yang ditolak beserta isian sebelumnya.
            'perbaikan' => $permohonan ? [
                'ulid' => $permohonan->ulid,
                'nomor' => $permohonan->nomor,
                'catatan_verifikasi' => $permohonan->catatan_verifikasi,
                'tujuan_asesmen' => $permohonan->tujuan_asesmen->value,
                'tujuan_asesmen_lainnya' => $permohonan->tujuan_asesmen_lainnya,
                'id_tuk' => $permohonan->id_tuk,
            ] : null,
            // URL dokumen yang sudah tersimpan per jenis: identitas dari profil, sisanya dari permohonan yang diperbaiki.
            'dokumenTersimpan' => collect(JenisDokumen::cases())->mapWithKeys(fn (JenisDokumen $jenis) => [
                $jenis->value => match (true) {
                    $jenis->identitas() => filled($dataDiri?->{$jenis->kolomDataDiri()}) ? route('sertifikasi.dokumen-saya', $jenis->value) : null,
                    $dokumenPermohonan?->has($jenis->value) ?? false => route('dokumen.permohonan', [$permohonan, $jenis->value]),
                    default => null,
                },
            ]),
            'options' => [
                // Daftar negara hanya untuk WNA, jadi Indonesia tidak ikut ditampilkan.
                'negara' => Negara::query()->where('nama_negara', '!=', 'Indonesia')->orderBy('nama_negara')->pluck('nama_negara', 'id_negara'),
                'id_indonesia' => $idIndonesia,
                'jenis_kelamin' => DataDiri::JENIS_KELAMIN,
                'pendidikan' => DataDiri::PENDIDIKAN,
                'tujuan_asesmen' => TujuanAsesmen::options(),
                'dokumen' => JenisDokumen::definisi(),
            ],
        ];
    }

    /**
     * Simpan isian APL-01. Tanpa $permohonan membuat permohonan baru; dengan $permohonan
     * (yang ditolak) memperbaruinya dan mengajukannya lagi.
     */
    private function simpanApl01(PermohonanApl01Request $request, PeriodePendaftaran $periode, SkemaSertifikasi $skema, ?PermohonanSertifikasi $permohonan): PermohonanSertifikasi
    {
        $user = $request->user();
        $data = $request->validated();
        $fileBaru = [];
        $fileLama = [];

        try {
            $permohonan = DB::transaction(function () use ($user, $data, $request, $periode, $skema, $permohonan, &$fileBaru, &$fileLama) {
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

                $isian = [
                    'tujuan_asesmen' => $data['tujuan_asesmen'],
                    'tujuan_asesmen_lainnya' => $data['tujuan_asesmen'] === TujuanAsesmen::Lainnya->value ? $data['tujuan_asesmen_lainnya'] : null,
                    'status' => StatusPermohonan::Diajukan,
                ];

                if ($permohonan) {
                    // Hasil verifikasi sebelumnya sudah tercatat di riwayat status, jadi aman dikosongkan.
                    $permohonan->update([
                        ...$isian,
                        'cek_dokumen' => null,
                        'catatan_verifikasi' => null,
                        'id_verifikator' => null,
                        'diverifikasi_pada' => null,
                    ]);
                    $permohonan->catatStatus(StatusPermohonan::Diajukan, 'Diajukan ulang setelah diperbaiki.', $user);

                    if ($permohonan->id_tuk !== (int) $data['id_tuk']) {
                        $permohonan->gantiLokasi((int) $data['id_tuk'], 'Diubah saat memperbaiki permohonan.', $user);
                    }
                } else {
                    $permohonan = PermohonanSertifikasi::create([
                        ...$isian,
                        'id_data_diri' => $dataDiri->id_data_diri,
                        'id_periode' => $periode->id_periode,
                        'id_skema' => $skema->id_skema,
                    ]);
                    $permohonan->catatStatus(StatusPermohonan::Diajukan, null, $user);
                    $permohonan->gantiLokasi((int) $data['id_tuk'], 'Dipilih saat pendaftaran.', $user);
                }

                $permohonan->setRelation('dataDiri', $dataDiri);

                // Bukti persyaratan dasar disimpan di folder milik permohonan ini; file yang diganti menimpa data lamanya.
                foreach (JenisDokumen::cases() as $jenis) {
                    if ($jenis->identitas() || ! $request->hasFile("dokumen.{$jenis->value}")) {
                        continue;
                    }

                    $file = $request->file("dokumen.{$jenis->value}");
                    $lama = $permohonan->dokumen()->where('jenis', $jenis)->first();
                    $fileLama[] = $lama?->path;

                    $permohonan->dokumen()->updateOrCreate(['jenis' => $jenis], [
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

        return $permohonan;
    }

    /**
     * @return list<array{id_tuk: int, ulid: string, nama: string, alamat: string}>
     */
    private function lokasiOptions(PeriodePendaftaran $periode, int $idSkema): array
    {
        return $periode->lokasiSkema($idSkema)
            ->map(fn (Tuk $tuk) => $tuk->ringkas())
            ->values()
            ->all();
    }

    private function pastikanPemilik(Request $request, PermohonanSertifikasi $permohonan): void
    {
        abort_unless($permohonan->dataDiri?->id_user === $request->user()->id_user, 404);
    }

    /**
     * Permohonan ditolak terakhir milik asesi untuk skema ini pada periode ini, bila ada.
     */
    private function permohonanDitolak(User $user, PeriodePendaftaran $periode, SkemaSertifikasi $skema): ?PermohonanSertifikasi
    {
        return $user->dataDiri?->permohonan()
            ->where('id_periode', $periode->id_periode)
            ->where('id_skema', $skema->id_skema)
            ->where('status', StatusPermohonan::Ditolak)
            ->latest('id_permohonan')
            ->first();
    }

    /**
     * Permohonan aktif (menunggu verifikasi/diterima) milik asesi pada periode ini, apa pun skemanya.
     */
    private function permohonanAktif(User $user, PeriodePendaftaran $periode, ?PermohonanSertifikasi $kecuali = null): ?PermohonanSertifikasi
    {
        return $user->dataDiri?->permohonan()
            ->with('skema:id_skema,judul')
            ->where('id_periode', $periode->id_periode)
            ->whereIn('status', StatusPermohonan::aktif())
            ->when($kecuali, fn ($query) => $query->whereKeyNot($kecuali->id_permohonan))
            ->first();
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

        if ($aktif = $this->permohonanAktif($user, $periode)) {
            return "Anda sudah mendaftar skema {$aktif->skema->judul} pada periode ini. Satu periode hanya untuk satu skema; batalkan permohonan tersebut bila salah memilih skema.";
        }

        if ($periode->kuotaPenuh()) {
            return "Kuota pendaftar periode {$periode->nama} sudah penuh.";
        }

        return null;
    }

    /**
     * Pesan alasan bila permohonan ini tidak bisa diperbaiki dan diajukan lagi, atau null bila boleh.
     */
    private function alasanTidakBisaPerbaiki(PermohonanSertifikasi $permohonan): ?string
    {
        $periode = $permohonan->periode;

        if ($permohonan->status !== StatusPermohonan::Ditolak) {
            return 'Hanya permohonan yang ditolak yang bisa diperbaiki.';
        }

        if ($periode->status() !== 'dibuka') {
            return "Periode {$periode->nama} sudah tidak menerima pendaftaran.";
        }

        if (! $periode->skema()->whereKey($permohonan->id_skema)->exists()) {
            return "Skema {$permohonan->skema->judul} sudah tidak dibuka pada periode ini.";
        }

        if ($aktif = $this->permohonanAktif($permohonan->dataDiri->user, $periode, $permohonan)) {
            return "Anda sudah mendaftar skema {$aktif->skema->judul} pada periode ini.";
        }

        if ($periode->kuotaPenuh()) {
            return "Kuota pendaftar periode {$periode->nama} sudah penuh.";
        }

        return null;
    }
}
