<?php

namespace App\Http\Controllers\Master;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Master\AsesorRequest;
use App\Models\Asesor;
use App\Models\User;
use App\Notifications\AsesorVerifikasiAkun;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
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
            ->with('user:id_user,email,created_at,must_change_password,email_verified_at')
            ->when($search !== '', function ($query) use ($search) {
                // lower() dipakai supaya pencarian tetap case-insensitive di PostgreSQL.
                $keyword = '%'.mb_strtolower($search).'%';

                $query->where(function ($query) use ($keyword) {
                    $query->whereRaw('lower(nama) like ?', [$keyword])
                        ->orWhereRaw('lower(kode_asesor) like ?', [$keyword])
                        ->orWhereRaw('lower(no_reg) like ?', [$keyword])
                        ->orWhereHas('user', fn ($query) => $query->whereRaw('lower(email) like ?', [$keyword]));
                });
            })
            ->orderBy('nama')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Asesor $asesor) => [
                'id_asesor' => $asesor->id_asesor,
                'ulid' => $asesor->ulid,
                'kode_asesor' => $asesor->kode_asesor,
                'nama' => $asesor->nama,
                'no_reg' => $asesor->no_reg,
                'email' => $asesor->user?->email,
                'dibuat_pada' => $asesor->user?->created_at?->translatedFormat('d M Y'),
                'status' => $this->status($asesor->user),
            ]);

        $akunAsesor = User::query()->where('role', UserRole::Asesor);

        return Inertia::render('master/asesor/index', [
            'asesor' => $asesor,
            'filters' => ['search' => $search],
            'stats' => [
                'total_asesor' => Asesor::count(),
                'menunggu_verifikasi' => (clone $akunAsesor)->whereNull('email_verified_at')->count(),
                'belum_ganti_password' => (clone $akunAsesor)->whereNotNull('email_verified_at')->where('must_change_password', true)->count(),
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
     * Simpan akun asesor baru lalu kirim email berisi password sementara & link verifikasi.
     */
    public function store(AsesorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'email' => $data['email'],
                // Diganti password sementara saat email dikirim; akun belum bisa login sampai diverifikasi.
                'password' => Str::password(32),
                'role' => UserRole::Asesor,
                'must_change_password' => true,
            ]);

            Asesor::create([
                'id_user' => $user->id_user,
                'nama' => $data['nama'],
                'no_reg' => $data['no_reg'],
            ]);

            return $user;
        });

        $this->kirimAktivasi($user, $data['nama']);

        return to_route('master.asesor.index')
            ->with('success', "Akun asesor {$data['nama']} berhasil dibuat. Password sementara & link verifikasi sudah dikirim ke {$data['email']}.");
    }

    /**
     * Form ubah akun asesor.
     */
    public function edit(Asesor $asesor): Response
    {
        $asesor->load('user:id_user,email,email_verified_at');

        return Inertia::render('master/asesor/edit', [
            'asesor' => [
                'id_asesor' => $asesor->id_asesor,
                'ulid' => $asesor->ulid,
                'nama' => $asesor->nama,
                'no_reg' => $asesor->no_reg,
                'email' => $asesor->user?->email,
                'terverifikasi' => (bool) $asesor->user?->isVerified(),
            ],
        ]);
    }

    /**
     * Perbarui data asesor dan email akun loginnya.
     */
    public function update(AsesorRequest $request, Asesor $asesor): RedirectResponse
    {
        $data = $request->validated();
        $user = $asesor->user;

        // Bila email akun yang belum aktif dikoreksi, password sementara & link verifikasi dikirim ulang ke email baru.
        $kirimUlang = $user && ! $user->isVerified() && $user->email !== $data['email'];

        DB::transaction(function () use ($data, $asesor, $user) {
            $asesor->update([
                'nama' => $data['nama'],
                'no_reg' => $data['no_reg'],
            ]);

            $user?->update(['email' => $data['email']]);
        });

        if ($kirimUlang) {
            $this->kirimAktivasi($user, $data['nama']);
        }

        return to_route('master.asesor.index')
            ->with('success', $kirimUlang
                ? "Data akun asesor diperbarui dan link verifikasi dikirim ulang ke {$data['email']}."
                : 'Data akun asesor berhasil diperbarui.');
    }

    /**
     * Kirim ulang link verifikasi untuk akun yang belum aktif.
     */
    public function resendVerification(Asesor $asesor): RedirectResponse
    {
        $user = $asesor->user;

        if (! $user || $user->isVerified()) {
            return back()->with('error', 'Akun asesor ini sudah terverifikasi.');
        }

        $this->kirimAktivasi($user, $asesor->nama);

        return back()->with('success', "Password sementara & link verifikasi dikirim ulang ke {$user->email}.");
    }

    /**
     * Buat password sementara baru lalu kirim bersama link verifikasi dalam satu email.
     * Password lama tidak tersimpan dalam bentuk asli, jadi setiap pengiriman membuat yang baru.
     */
    private function kirimAktivasi(User $user, string $nama): void
    {
        $password = Str::password(12, symbols: false);

        $user->forceFill([
            'password' => $password,
            'must_change_password' => true,
        ])->save();

        $user->notify(new AsesorVerifikasiAkun($nama, $password));
    }

    /**
     * Hapus akun asesor yang belum diverifikasi beserta akun loginnya.
     */
    public function destroy(Asesor $asesor): RedirectResponse
    {
        // Akun yang sudah aktif dipertahankan karena sudah dipakai pemiliknya.
        if ($asesor->user?->isVerified()) {
            return back()->with('error', "Akun {$asesor->nama} sudah terverifikasi dan tidak dapat dihapus.");
        }

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

    /**
     * Tahap akun: menunggu verifikasi → masih password sementara → aktif.
     */
    private function status(?User $user): string
    {
        return match (true) {
            ! $user?->isVerified() => 'menunggu_verifikasi',
            $user->must_change_password => 'password_sementara',
            default => 'aktif',
        };
    }
}
