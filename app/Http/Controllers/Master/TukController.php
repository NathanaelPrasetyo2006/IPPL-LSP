<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\TukRequest;
use App\Models\Tuk;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TukController extends Controller
{
    /**
     * Daftar lokasi TUK.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');

        if (! array_key_exists($status, Tuk::STATUS)) {
            $status = '';
        }

        $tuk = Tuk::query()
            ->when($search !== '', function ($query) use ($search) {
                // lower() dipakai supaya pencarian tetap case-insensitive di PostgreSQL.
                $keyword = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('lower(kode) like ?', [$keyword])
                        ->orWhereRaw('lower(nama) like ?', [$keyword])
                        ->orWhereRaw('lower(kota) like ?', [$keyword]);
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->orderBy('kode')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('master/tuk/index', [
            'tuk' => $tuk,
            'filters' => ['search' => $search, 'status' => $status],
            'stats' => [
                'total_tuk' => Tuk::count(),
                'total_kota' => Tuk::query()->distinct()->count('kota'),
                'tuk_aktif' => Tuk::query()->where('status', 'aktif')->count(),
            ],
        ]);
    }

    /**
     * Form tambah lokasi TUK.
     */
    public function create(): Response
    {
        return Inertia::render('master/tuk/create', [
            'options' => $this->options(),
        ]);
    }

    /**
     * Simpan lokasi TUK baru.
     */
    public function store(TukRequest $request): RedirectResponse
    {
        Tuk::create($request->validated());

        return to_route('master.tuk.index')
            ->with('success', 'Lokasi TUK berhasil ditambahkan.');
    }

    /**
     * Form ubah lokasi TUK.
     */
    public function edit(Tuk $tuk): Response
    {
        return Inertia::render('master/tuk/edit', [
            'tuk' => $tuk->only(['id_tuk', 'kode', 'nama', 'jenis', 'alamat', 'kota', 'penanggung_jawab', 'telepon', 'status']),
            'options' => $this->options(),
        ]);
    }

    /**
     * Perbarui lokasi TUK.
     */
    public function update(TukRequest $request, Tuk $tuk): RedirectResponse
    {
        $tuk->update($request->validated());

        return to_route('master.tuk.index')
            ->with('success', 'Lokasi TUK berhasil diperbarui.');
    }

    /**
     * Hapus lokasi TUK (soft delete, data masih bisa dipulihkan).
     */
    public function destroy(Tuk $tuk): RedirectResponse
    {
        $tuk->delete();

        return to_route('master.tuk.index')
            ->with('success', 'Lokasi TUK berhasil dihapus.');
    }

    /**
     * Pilihan dropdown untuk form tambah/ubah.
     *
     * @return array<string, array<string, string>>
     */
    private function options(): array
    {
        return [
            'jenis' => Tuk::JENIS,
            'status' => Tuk::STATUS,
        ];
    }
}
