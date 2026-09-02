<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\SkemaSertifikasiRequest;
use App\Models\SkemaSertifikasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                'total_unit' => DB::table('unit_kompetensi')->count(),
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
            'skema' => $skema->only(['id_skema', 'judul', 'nomor']),
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
     * Hapus skema sertifikasi beserta unit kompetensinya.
     */
    public function destroy(SkemaSertifikasi $skema): RedirectResponse
    {
        // Relasi asesmen_mandiri memakai cascade delete, jadi skema yang sudah
        // dipakai asesmen tidak boleh dihapus agar riwayat asesmen tidak ikut hilang.
        if (DB::table('asesmen_mandiri')->where('id_skema', $skema->id_skema)->exists()) {
            return back()->with('error', 'Skema sertifikasi sudah dipakai pada asesmen dan tidak dapat dihapus.');
        }

        $skema->delete();

        return to_route('master.skema-sertifikasi.index')
            ->with('success', 'Skema sertifikasi berhasil dihapus.');
    }
}
