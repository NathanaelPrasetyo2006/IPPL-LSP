import RoleAvatar from '@/components/auth/role-avatar';
import { ROLES } from '@/components/auth/roles';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type SharedData, type UserRole } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BadgeCheck,
    CalendarRange,
    ClipboardCheck,
    ClipboardList,
    FileSignature,
    IdCard,
    type LucideIcon,
    MapPin,
    UserCheck,
    UserPen,
} from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface Shortcut {
    title: string;
    description: string;
    href: string;
    icon: LucideIcon;
    tone: string;
}

// Pintasan di dashboard mengikuti hak akses tiap peran.
const SHORTCUTS: Record<UserRole, Shortcut[]> = {
    admin: [
        {
            title: 'Verifikasi APL-01',
            description: 'Periksa berkas dan tetapkan status permohonan.',
            href: '/pendaftaran/verifikasi',
            icon: ClipboardCheck,
            tone: 'bg-amber-500',
        },
        {
            title: 'Periode Pendaftaran',
            description: 'Buka gelombang pendaftaran dan pilih skemanya.',
            href: '/pendaftaran/periode',
            icon: CalendarRange,
            tone: 'bg-teal-600',
        },
        {
            title: 'Skema Sertifikasi',
            description: 'Kelola skema dan unit kompetensinya.',
            href: '/master/skema-sertifikasi',
            icon: BadgeCheck,
            tone: 'bg-blue-600',
        },
        {
            title: 'Lokasi TUK',
            description: 'Kelola Tempat Uji Kompetensi.',
            href: '/master/tuk',
            icon: MapPin,
            tone: 'bg-violet-500',
        },
        {
            title: 'Akun Asesor',
            description: 'Buat akun dan pantau verifikasi asesor.',
            href: '/master/asesor',
            icon: UserCheck,
            tone: 'bg-emerald-600',
        },
    ],
    asesor: [
        {
            title: 'Biodata Asesor',
            description: 'Lengkapi data diri dan pas foto Anda.',
            href: '/asesor/biodata',
            icon: UserPen,
            tone: 'bg-emerald-600',
        },
        {
            title: 'Kartu Tanda Asesor',
            description: 'Unduh atau cetak kartu identitas asesor.',
            href: '/asesor/kartu',
            icon: IdCard,
            tone: 'bg-amber-500',
        },
        {
            title: 'Skema Sertifikasi',
            description: 'Lihat skema dan unit kompetensi yang diujikan.',
            href: '/master/skema-sertifikasi',
            icon: BadgeCheck,
            tone: 'bg-blue-600',
        },
        {
            title: 'Lokasi TUK',
            description: 'Lihat daftar Tempat Uji Kompetensi.',
            href: '/master/tuk',
            icon: MapPin,
            tone: 'bg-violet-500',
        },
    ],
    asesi: [
        {
            title: 'Pendaftaran Sertifikasi',
            description: 'Pilih skema pada periode yang dibuka dan isi APL-01.',
            href: '/sertifikasi',
            icon: FileSignature,
            tone: 'bg-teal-600',
        },
    ],
};

export default function Dashboard() {
    const { flash, auth } = usePage<SharedData>().props;
    const role = auth.user.role;
    const meta = ROLES[role];
    const shortcuts = SHORTCUTS[role];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
                {flash.success && (
                    <Alert className="border-emerald-500/40 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                <div className="bg-card flex items-center gap-4 rounded-xl border p-5 shadow-xs">
                    <div className={cn('size-16 shrink-0 overflow-hidden rounded-full', meta.avatarClass)}>
                        <RoleAvatar role={role} />
                    </div>

                    <div className="min-w-0">
                        <p className="text-muted-foreground text-sm">Selamat datang,</p>
                        <h1 className="truncate text-xl font-bold tracking-tight md:text-2xl">{auth.user.email}</h1>
                        <span className="bg-muted mt-1 inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium">Masuk sebagai {meta.label}</span>
                    </div>
                </div>

                {shortcuts.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-3">
                        {shortcuts.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className="group bg-card hover:border-foreground/20 flex flex-col gap-4 rounded-xl border p-5 shadow-xs transition-all hover:shadow-md"
                            >
                                <span className={cn('flex size-10 items-center justify-center rounded-lg text-white', item.tone)}>
                                    <item.icon className="size-5" />
                                </span>

                                <div className="flex-1">
                                    <h2 className="font-semibold">{item.title}</h2>
                                    <p className="text-muted-foreground mt-1 text-sm">{item.description}</p>
                                </div>

                                <span className="text-muted-foreground group-hover:text-foreground inline-flex items-center gap-1.5 text-sm font-medium transition-colors">
                                    Buka
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </Link>
                        ))}
                    </div>
                ) : (
                    <div className="bg-card flex items-start gap-4 rounded-xl border p-5 shadow-xs">
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-teal-600 text-white">
                            <ClipboardList className="size-5" />
                        </span>
                        <div>
                            <h2 className="font-semibold">Pendaftaran uji kompetensi</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Fitur pengisian data diri (APL-01) dan pendaftaran skema sedang disiapkan. Anda akan dapat mengaksesnya dari halaman
                                ini.
                            </p>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
