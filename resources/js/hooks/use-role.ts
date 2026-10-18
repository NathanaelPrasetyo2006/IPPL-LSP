import { type SharedData, type UserRole } from '@/types';
import { usePage } from '@inertiajs/react';

/**
 * Peran pengguna yang sedang login. Dipakai untuk menyembunyikan aksi kelola
 * dari asesor; batas akses sebenarnya tetap dijaga middleware `role` di server.
 */
export function useRole(): { role: UserRole; isAdmin: boolean } {
    const { auth } = usePage<SharedData>().props;

    return { role: auth.user.role, isAdmin: auth.user.role === 'admin' };
}
