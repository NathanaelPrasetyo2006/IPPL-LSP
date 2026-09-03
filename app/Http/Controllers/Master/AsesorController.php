<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AsesorRequest;
use App\Models\Asesor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AsesorController extends Controller
{
    /**
     * Daftar akun asesor.
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));

        $asesor = Asesor::query()
            ->with('user:id_user,email,created_at')
            ->when($search !== '', function ($query) use ($search) {
                // lower() dipakai supaya pencarian tetap case-insensitive di PostgreSQL.
                $keyword = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('lower(nama) like ?', [$keyword])
                        ->orWhereRaw('lower(no_reg) like ?', [$keyword])
                        ->orWhereHas('user', fn ($query) => $query->whereRaw('lower(email) like ?', [$keyword]));
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Asesor $asesor) => [
                'id_asesor' => $asesor->id_asesor,
                'nama' => $asesor->nama,
                'no_reg' => $asesor->no_reg,
                'email' => $asesor->user?->email,
                'dibuat_pada' => $asesor->user?->created_at?->translatedFormat('d M Y'),
            ]);

        return Inertia::render('master/asesor/index', [
            'asesor' => $asesor,
            'filters' => ['search' => $search],
            'stats' => [
                'total_asesor' => Asesor::count(),
                'akun_bulan_ini' => User::query()->whereHas('asesor')->where('created_at', '>=', now()->startOfMonth())->count(),
                'sedang_bertugas' => DB::table('asesmen_mandiri')->distinct()->count('id_asesor'),
            ],
        ]);
    }

    /**
     * Form pembuatan akun asesor.
     */
    public function create(): Response
    {
        return Inertia::render('master/asesor/create');
    }

    /**
     * Simpan akun asesor baru beserta akun loginnya.
     */
    public function store(AsesorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            Asesor::create([
                'id_user' => $user->id_user,
                'nama' => $data['nama'],
                'no_reg' => $data['no_reg'],
            ]);
        });

        return to_route('master.asesor.index')
            ->with('success', "Akun asesor {$data['nama']} berhasil dibuat. Serahkan password sementara ke asesor yang bersangkutan.");
    }

    /**
     * Form ubah akun asesor.
     */
    public function edit(Asesor $asesor): Response
    {
        $asesor->load('user:id_user,email');

        return Inertia::render('master/asesor/edit', [
            'asesor' => [
                'id_asesor' => $asesor->id_asesor,
                'nama' => $asesor->nama,
                'no_reg' => $asesor->no_reg,
                'email' => $asesor->user?->email,
            ],
        ]);
    }

    /**
     * Perbarui data asesor dan akun loginnya.
     */
    public function update(AsesorRequest $request, Asesor $asesor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $asesor) {
            $asesor->update([
                'nama' => $data['nama'],
                'no_reg' => $data['no_reg'],
            ]);

            $account = ['email' => $data['email']];

            // Password hanya ditimpa kalau admin mengisi password sementara yang baru.
            if (filled($data['password'] ?? null)) {
                $account['password'] = $data['password'];
            }

            $asesor->user?->update($account);
        });

        return to_route('master.asesor.index')
            ->with('success', 'Data akun asesor berhasil diperbarui.');
    }

    /**
     * Hapus akun asesor beserta akun loginnya.
     */
    public function destroy(Asesor $asesor): RedirectResponse
    {
        // asesmen_mandiri.id_asesor tanpa cascade, jadi asesor yang sudah bertugas
        // akan ditolak database. Dicegah lebih awal supaya pesannya jelas.
        if (DB::table('asesmen_mandiri')->where('id_asesor', $asesor->id_asesor)->exists()) {
            return back()->with('error', 'Asesor sudah ditugaskan pada asesmen dan tidak dapat dihapus.');
        }

        // Baris asesor ikut terhapus lewat cascade dari users.
        DB::transaction(fn () => $asesor->user?->delete() ?? $asesor->delete());

        return to_route('master.asesor.index')
            ->with('success', 'Akun asesor berhasil dihapus.');
    }
}
