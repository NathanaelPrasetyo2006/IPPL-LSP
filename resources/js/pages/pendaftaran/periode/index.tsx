import ConfirmDeleteDialog from '@/components/master/confirm-delete-dialog';
import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import { StatusPeriodeBadge } from '@/components/pendaftaran/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type PeriodePendaftaran, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarRange, ClipboardCheck, DoorClosed, DoorOpen, LoaderCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendaftaran', href: '/pendaftaran/periode' },
    { title: 'Periode Pendaftaran', href: '/pendaftaran/periode' },
];

interface IndexProps {
    periode: Paginated<PeriodePendaftaran>;
    stats: {
        total_periode: number;
        periode_dibuka: number;
        menunggu_verifikasi: number;
    };
}

export default function PeriodeIndex({ periode, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;

    const [target, setTarget] = useState<PeriodePendaftaran | null>(null);
    const [deleting, setDeleting] = useState(false);
    const [togglingId, setTogglingId] = useState<number | null>(null);

    const destroy = () => {
        if (!target) {
            return;
        }

        router.delete(route('pendaftaran.periode.destroy', target.id_periode), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setTarget(null);
            },
        });
    };

    const toggle = (item: PeriodePendaftaran) => {
        router.patch(
            route('pendaftaran.periode.toggle', item.id_periode),
            {},
            {
                preserveScroll: true,
                onStart: () => setTogglingId(item.id_periode),
                onFinish: () => setTogglingId(null),
            },
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Periode Pendaftaran" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Periode Pendaftaran</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Buka dan atur gelombang pendaftaran sertifikasi beserta skema yang ditawarkan.
                        </p>
                    </div>

                    <Button asChild className="shrink-0">
                        <Link href={route('pendaftaran.periode.create')}>
                            <Plus />
                            Buka Periode
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
                    <StatCard icon={CalendarRange} tone="bg-blue-600" value={stats.total_periode} label="Total Periode" />
                    <StatCard icon={DoorOpen} tone="bg-emerald-600" value={stats.periode_dibuka} label="Sedang Dibuka" />
                    <Link href={route('pendaftaran.verifikasi.index')} className="rounded-xl transition-shadow hover:shadow-md">
                        <StatCard
                            icon={ClipboardCheck}
                            tone="bg-amber-500"
                            value={stats.menunggu_verifikasi}
                            label="Permohonan Menunggu Verifikasi"
                        />
                    </Link>
                </div>

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="border-b p-4">
                        <h2 className="text-base font-semibold">Daftar Periode</h2>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="pl-5">Periode</TableHead>
                                <TableHead className="w-56">Jadwal</TableHead>
                                <TableHead>Skema</TableHead>
                                <TableHead className="w-32">Pendaftar</TableHead>
                                <TableHead className="w-32">Status</TableHead>
                                <TableHead className="w-32 pr-5 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {periode.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={6} className="text-muted-foreground py-14 text-center">
                                        Belum ada periode pendaftaran. Klik <span className="text-foreground font-medium">Buka Periode</span> untuk
                                        membuat.
                                    </TableCell>
                                </TableRow>
                            )}

                            {periode.data.map((item) => (
                                <TableRow key={item.id_periode}>
                                    <TableCell className="pl-5">
                                        <Link
                                            href={route('pendaftaran.periode.edit', item.id_periode)}
                                            className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                        >
                                            {item.nama}
                                        </Link>
                                    </TableCell>

                                    <TableCell className="text-sm whitespace-nowrap">
                                        {item.tanggal_buka} – {item.tanggal_tutup}
                                    </TableCell>

                                    <TableCell>
                                        <div className="flex max-w-md flex-wrap gap-1">
                                            {item.skema.slice(0, 3).map((skema) => (
                                                <span key={skema.id_skema} className="bg-muted rounded-md px-2 py-0.5 text-xs" title={skema.nomor}>
                                                    {skema.judul}
                                                </span>
                                            ))}
                                            {item.skema.length > 3 && (
                                                <span className="text-muted-foreground px-1 py-0.5 text-xs">+{item.skema.length - 3} lainnya</span>
                                            )}
                                        </div>
                                    </TableCell>

                                    <TableCell className="text-sm tabular-nums">
                                        <Link
                                            href={route('pendaftaran.verifikasi.index', { periode: item.id_periode, status: 'semua' })}
                                            className="hover:underline"
                                        >
                                            {item.kuota ? `${item.kuota_terpakai} / ${item.kuota}` : item.jumlah_permohonan}
                                        </Link>
                                        {item.permohonan_menunggu > 0 && (
                                            <span className="block text-xs text-amber-600 dark:text-amber-400">
                                                {item.permohonan_menunggu} menunggu
                                            </span>
                                        )}
                                    </TableCell>

                                    <TableCell>
                                        <StatusPeriodeBadge status={item.status} />
                                    </TableCell>

                                    <TableCell className="pr-5">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title={item.aktif ? 'Tutup pendaftaran sekarang' : 'Aktifkan kembali'}
                                                className="size-8 text-sky-600 hover:bg-sky-50 hover:text-sky-700 dark:text-sky-400 dark:hover:bg-sky-950/50"
                                                disabled={togglingId === item.id_periode}
                                                onClick={() => toggle(item)}
                                            >
                                                {togglingId === item.id_periode ? (
                                                    <LoaderCircle className="animate-spin" />
                                                ) : item.aktif ? (
                                                    <DoorClosed />
                                                ) : (
                                                    <DoorOpen />
                                                )}
                                                <span className="sr-only">{item.aktif ? `Tutup ${item.nama}` : `Aktifkan ${item.nama}`}</span>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                                asChild
                                                title="Ubah periode"
                                            >
                                                <Link href={route('pendaftaran.periode.edit', item.id_periode)}>
                                                    <Pencil />
                                                    <span className="sr-only">Ubah {item.nama}</span>
                                                </Link>
                                            </Button>

                                            <span title={item.jumlah_permohonan > 0 ? 'Sudah ada pendaftar, tidak dapat dihapus' : 'Hapus periode'}>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                    disabled={item.jumlah_permohonan > 0}
                                                    onClick={() => setTarget(item)}
                                                >
                                                    <Trash2 />
                                                    <span className="sr-only">Hapus {item.nama}</span>
                                                </Button>
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {periode.total > 0 && (
                        <TablePagination
                            from={periode.from}
                            to={periode.to}
                            total={periode.total}
                            prevPageUrl={periode.prev_page_url}
                            nextPageUrl={periode.next_page_url}
                        />
                    )}
                </div>
            </div>

            <ConfirmDeleteDialog
                open={target !== null}
                onOpenChange={(open) => !open && setTarget(null)}
                processing={deleting}
                onConfirm={destroy}
                title="Hapus periode pendaftaran?"
                description={
                    <>
                        Periode <span className="text-foreground font-medium">{target?.nama}</span> belum memiliki pendaftar dan akan dihapus
                        permanen.
                    </>
                }
            />
        </AppLayout>
    );
}
