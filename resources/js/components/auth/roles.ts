import { type UserRole } from '@/types';

interface RoleMeta {
    label: string;
    /** Deskripsi singkat di kartu portal. */
    summary: string;
    /** Deskripsi di bawah judul formulir login. */
    loginDescription: string;
    /** Warna latar lingkaran avatar. */
    avatarClass: string;
    /** Warna aksen saat kartu portal di-hover/fokus. */
    hoverClass: string;
}

export const ROLE_ORDER: UserRole[] = ['admin', 'asesor', 'asesi'];

export const ROLES: Record<UserRole, RoleMeta> = {
    admin: {
        label: 'Admin LSP',
        summary: 'Kelola skema, TUK, jadwal, dan akun pengguna.',
        loginDescription: 'Masuk untuk mengelola data master dan proses sertifikasi LSP.',
        avatarClass: 'bg-violet-100 dark:bg-violet-500/15',
        hoverClass: 'hover:border-violet-400/70 focus-visible:border-violet-400/70 dark:hover:border-violet-500/50',
    },
    asesor: {
        label: 'Asesor',
        summary: 'Nilai asesi dan kelola jadwal uji kompetensi.',
        loginDescription: 'Gunakan email dan password yang diberikan admin LSP.',
        avatarClass: 'bg-sky-100 dark:bg-sky-500/15',
        hoverClass: 'hover:border-sky-400/70 focus-visible:border-sky-400/70 dark:hover:border-sky-500/50',
    },
    asesi: {
        label: 'Asesi',
        summary: 'Daftar uji kompetensi dan pantau sertifikasi Anda.',
        loginDescription: 'Masuk untuk mendaftar uji kompetensi dan memantau proses sertifikasi Anda.',
        avatarClass: 'bg-teal-100 dark:bg-teal-500/15',
        hoverClass: 'hover:border-teal-400/70 focus-visible:border-teal-400/70 dark:hover:border-teal-500/50',
    },
};
