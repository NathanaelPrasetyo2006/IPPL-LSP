<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SkemaSertifikasiRequest;
use App\Models\SkemaSertifikasi;
use App\Models\UnitKompetensi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SkemaSertifikasiController extends Controller
{
    /**
     * Daftar skema sertifikasi.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $skema = SkemaSertifikasi::query()
            ->withCount('unitKompetensi')
            // Unit ikut dimuat untuk popup detail; cukup untuk 10 skema per halaman.
            ->with(['unitKompetensi' => fn ($query) => $query
                ->select(['id_unit_kompetensi', 'ulid', 'id_skema', 'kode', 'judul', 'urutan'])
                ->orderBy('urutan')
                ->orderBy('kode')])
            ->when($search !== '', function ($query) use ($search) {
                // lower() dipakai supaya pencarian tetap case-insensitive di PostgreSQL.
                $keyword = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('lower(judul) like ?', [$keyword])
                        ->orWhereRaw('lower(nomor) like ?', [$keyword]);
                });
            })
            ->orderBy('nomor')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/skema-sertifikasi/index', [
            'skema' => $skema,
            'filters' => ['search' => $search],
            'stats' => [
                'total_skema' => SkemaSertifikasi::count(),
                // whereHas mengabaikan unit milik skema yang sudah dihapus.
                'total_unit' => UnitKompetensi::query()->whereHas('skema')->count(),
                'skema_tanpa_unit' => SkemaSertifikasi::query()->doesntHave('unitKompetensi')->count(),
            ],
        ]);
    }

    /**
     * Form tambah skema sertifikasi.
     */
    public function create(): Response
    {
        return Inertia::render('master/skema-sertifikasi/create');
    }

    /**
     * Simpan skema sertifikasi baru.
     */
    public function store(SkemaSertifikasiRequest $request): RedirectResponse
    {
        SkemaSertifikasi::create($request->validated());

        return to_route('master.skema-sertifikasi.index')
            ->with('success', 'Skema sertifikasi berhasil ditambahkan.');
    }

    /**
     * Form ubah skema sertifikasi.
     */
    public function edit(SkemaSertifikasi $skema): Response
    {
        return Inertia::render('master/skema-sertifikasi/edit', [
            'skema' => $skema->only(['id_skema', 'ulid', 'judul', 'nomor']),
        ]);
    }

    /**
     * Perbarui skema sertifikasi.
     */
    public function update(SkemaSertifikasiRequest $request, SkemaSertifikasi $skema): RedirectResponse
    {
        $skema->update($request->validated());

        return to_route('master.skema-sertifikasi.index')
            ->with('success', 'Skema sertifikasi berhasil diperbarui.');
    }

    /**
     * Hapus skema sertifikasi (soft delete).
     *
     * Baris di database tetap ada, jadi unit kompetensi dan riwayat asesmen
     * yang merujuk skema ini tidak ikut hilang dan masih bisa dipulihkan.
     */
    public function destroy(SkemaSertifikasi $skema): RedirectResponse
    {
        $skema->delete();

        return to_route('master.skema-sertifikasi.index')
            ->with('success', 'Skema sertifikasi berhasil dihapus.');
    }
}
