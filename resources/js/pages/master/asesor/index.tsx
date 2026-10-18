import ConfirmDeleteDialog from '@/components/master/confirm-delete-dialog';
import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type AsesorAccount, type AsesorStatus, type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CircleCheck, KeyRound, LoaderCircle, MailWarning, Pencil, Plus, Search, Send, Trash2, UserCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/asesor',
    },
    {
        title: 'Akun Asesor',
        href: '/master/asesor',
    },
];

interface IndexProps {
    asesor: Paginated<AsesorAccount>;
    filters: { search: string };
    stats: {
        total_asesor: number;
        menunggu_verifikasi: number;
        belum_ganti_password: number;
    };
}

const STATUS: Record<AsesorStatus, { label: string; icon: typeof KeyRound; className: string }> = {
    menunggu_verifikasi: {
        label: 'Menunggu verifikasi',
        icon: MailWarning,
        className: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-500/30',
    },
    password_sementara: {
        label: 'Password sementara',
        icon: KeyRound,
        className: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-950/50 dark:text-sky-400 dark:ring-sky-500/30',
    },
    aktif: {
        label: 'Aktif',
        icon: CircleCheck,
        className: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30',
    },
};

function StatusBadge({ status }: { status: AsesorStatus }) {
    const { label, icon: Icon, className } = STATUS[status];

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                className,
            )}
        >
            <Icon className="size-3" />
            {label}
        </span>
    );
}

export default function AsesorIndex({ asesor, filters, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const getInitials = useInitials();

    const [search, setSearch] = useState(filters.search);
    const [target, setTarget] = useState<AsesorAccount | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [resendingId, setResendingId] = useState<number | null>(null);
    const isFirstRender = useRef(true);

    // Pencarian dikirim setelah user berhenti mengetik supaya tidak membanjiri request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('master.asesor.index'), search ? { search } : {}, {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const destroy = () => {
        if (!target) {
            return;
        }

        router.delete(route('master.asesor.destroy', target.id_asesor), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setTarget(null);
            },
        });
    };

    const resend = (item: AsesorAccount) => {
        router.post(
            route('master.asesor.resend-verification', item.id_asesor),
            {},
            {
                preserveScroll: true,
                onStart: () => setResendingId(item.id_asesor),
                onFinish: () => setResendingId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Akun Asesor" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Akun Dasar Asesor</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Buat akun asesor; asesor mengaktifkan akunnya sendiri lewat link verifikasi di email.
                        </p>
                    </div>

                    <Button asChild className="shrink-0">
                        <Link href={route('master.asesor.create')}>
                            <Plus />
                            Buat Akun Asesor
                        </Link>
                    </Button>
                </div>

                {flash.success && (
                    <Alert className="border-emerald-500/40 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {flash.error && (
                    <Alert variant="destructive">
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard icon={UserCheck} tone="bg-blue-600" value={stats.total_asesor} label="Total Akun Asesor" />
                    <StatCard icon={MailWarning} tone="bg-amber-500" value={stats.menunggu_verifikasi} label="Menunggu Verifikasi Email" />
                    <StatCard icon={KeyRound} tone="bg-violet-500" value={stats.belum_ganti_password} label="Masih Password Sementara" />
                </div>

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-base font-semibold">Daftar Akun Asesor</h2>

                        <div className="relative sm:w-72">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama, no. reg, atau email..."
                                className="pl-9"
                                aria-label="Cari akun asesor"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-14 pl-5">No</TableHead>
                                <TableHead>Nama Asesor</TableHead>
                                <TableHead className="w-52">No. Registrasi / MET</TableHead>
                                <TableHead className="w-64">Email</TableHead>
                                <TableHead className="w-48">Status Akun</TableHead>
                                <TableHead className="w-32 pr-5 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {asesor.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={6} className="text-muted-foreground py-14 text-center">
                                        {filters.search ? 'Tidak ada asesor yang cocok dengan pencarian.' : 'Belum ada akun asesor.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {asesor.data.map((item, index) => {
                                const belumVerifikasi = item.status === 'menunggu_verifikasi';

                                return (
                                    <TableRow key={item.id_asesor}>
                                        <TableCell className="text-muted-foreground pl-5 tabular-nums">{(asesor.from ?? 0) + index}</TableCell>

                                        <TableCell>
                                            <div className="flex items-center gap-3">
                                                <span className="bg-muted text-muted-foreground flex size-8 shrink-0 items-center justify-center rounded-full text-xs font-semibold">
                                                    {getInitials(item.nama)}
                                                </span>

                                                <Link
                                                    href={route('master.asesor.edit', item.id_asesor)}
                                                    className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                                >
                                                    {item.nama}
                                                </Link>
                                            </div>
                                        </TableCell>

                                        <TableCell className="font-medium tracking-tight">{item.no_reg}</TableCell>

                                        <TableCell className="text-muted-foreground">{item.email ?? '-'}</TableCell>

                                        <TableCell>
                                            <StatusBadge status={item.status} />
                                        </TableCell>

                                        <TableCell className="pr-5">
                                            <div className="flex justify-end gap-1">
                                                {belumVerifikasi && (
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Kirim ulang link verifikasi"
                                                        className="size-8 text-sky-600 hover:bg-sky-50 hover:text-sky-700 dark:text-sky-400 dark:hover:bg-sky-950/50"
                                                        disabled={resendingId === item.id_asesor}
                                                        onClick={() => resend(item)}
                                                    >
                                                        {resendingId === item.id_asesor ? <LoaderCircle className="animate-spin" /> : <Send />}
                                                        <span className="sr-only">Kirim ulang link verifikasi ke {item.nama}</span>
                                                    </Button>
                                                )}

                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                                    asChild
                                                    title="Ubah akun"
                                                >
                                                    <Link href={route('master.asesor.edit', item.id_asesor)}>
                                                        <Pencil />
                                                        <span className="sr-only">Ubah {item.nama}</span>
                                                    </Link>
                                                </Button>

                                                {/* span menjaga tooltip tetap muncul walau tombolnya nonaktif. */}
                                                <span title={belumVerifikasi ? 'Hapus akun' : 'Akun sudah terverifikasi dan tidak dapat dihapus'}>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                        disabled={!belumVerifikasi}
                                                        onClick={() => setTarget(item)}
                                                    >
                                                        <Trash2 />
                                                        <span className="sr-only">Hapus {item.nama}</span>
                                                    </Button>
                                                </span>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>

                    {asesor.total > 0 && (
                        <TablePagination
                            from={asesor.from}
                            to={asesor.to}
                            total={asesor.total}
                            prevPageUrl={asesor.prev_page_url}
                            nextPageUrl={asesor.next_page_url}
                        />
                    )}
                </div>
            </div>

            <ConfirmDeleteDialog
                open={target !== null}
                onOpenChange={(open) => !open && setTarget(null)}
                processing={deleting}
                onConfirm={destroy}
                title="Hapus akun asesor?"
                description={
                    <>
                        Akun <span className="text-foreground font-medium">{target?.nama}</span> ({target?.no_reg}) belum diverifikasi dan akan
                        dihapus permanen beserta akun loginnya.
                    </>
                }
                note="Link verifikasi yang sudah terkirim ke email asesor otomatis tidak berlaku lagi."
            />
        </AppLayout>
    );
}
