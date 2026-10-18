import ConfirmDeleteDialog from '@/components/master/confirm-delete-dialog';
import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useRole } from '@/hooks/use-role';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Paginated, type SharedData, type Tuk, type TukOptions } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Building2, CircleCheckBig, MapPin, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/tuk',
    },
    {
        title: 'Lokasi TUK',
        href: '/master/tuk',
    },
];

interface IndexProps {
    tuk: Paginated<Tuk>;
    filters: { search: string; status: string };
    options: TukOptions;
    stats: {
        total_tuk: number;
        total_kota: number;
        tuk_aktif: number;
    };
}

const statusTone: Record<string, string> = {
    aktif: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30',
    nonaktif: 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-950/50 dark:text-rose-400 dark:ring-rose-500/30',
};

export default function TukIndex({ tuk, filters, options, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const { isAdmin } = useRole();

    const [search, setSearch] = useState(filters.search);
    const [target, setTarget] = useState<Tuk | null>(null);
    const [deleting, setDeleting] = useState(false);
    const isFirstRender = useRef(true);

    // Disimpan di ref supaya efek pencarian tidak perlu ikut bereaksi saat filter status berubah.
    const statusRef = useRef(filters.status);
    statusRef.current = filters.status;

    const visit = (nextSearch: string, nextStatus: string) => {
        const query: Record<string, string> = {};

        if (nextSearch) {
            query.search = nextSearch;
        }

        if (nextStatus) {
            query.status = nextStatus;
        }

        router.get(route('master.tuk.index'), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Pencarian dikirim setelah user berhenti mengetik supaya tidak membanjiri request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => visit(search, statusRef.current), 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const destroy = () => {
        if (!target) {
            return;
        }

        router.delete(route('master.tuk.destroy', target.id_tuk), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setTarget(null);
            },
        });
    };

    const statusFilters = [{ value: '', label: 'Semua' }, ...Object.entries(options.status).map(([value, label]) => ({ value, label }))];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Lokasi TUK" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Master Data Lokasi TUK</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {isAdmin
                                ? 'Kelola daftar Tempat Uji Kompetensi beserta status kelayakannya.'
                                : 'Daftar Tempat Uji Kompetensi beserta status kelayakannya.'}
                        </p>
                    </div>

                    {isAdmin && (
                        <Button asChild className="shrink-0">
                            <Link href={route('master.tuk.create')}>
                                <Plus />
                                Tambah TUK
                            </Link>
                        </Button>
                    )}
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
                    <StatCard icon={MapPin} tone="bg-blue-600" value={stats.total_tuk} label="Total Lokasi TUK" />
                    <StatCard icon={Building2} tone="bg-violet-500" value={stats.total_kota} label="Kota Terjangkau" />
                    <StatCard icon={CircleCheckBig} tone="bg-emerald-600" value={stats.tuk_aktif} label="TUK Berstatus Aktif" />
                </div>

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="flex flex-wrap gap-2">
                            {statusFilters.map((option) => {
                                const active = filters.status === option.value;

                                return (
                                    <button
                                        key={option.value || 'semua'}
                                        type="button"
                                        onClick={() => visit(search, option.value)}
                                        aria-pressed={active}
                                        className={cn(
                                            'focus-visible:ring-ring rounded-full border px-4 py-1.5 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:outline-hidden',
                                            active
                                                ? 'bg-foreground text-background border-transparent'
                                                : 'bg-background hover:bg-muted text-muted-foreground hover:text-foreground',
                                        )}
                                    >
                                        {option.label}
                                    </button>
                                );
                            })}
                        </div>

                        <div className="relative lg:w-72">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari kode, nama, atau kota..."
                                className="pl-9"
                                aria-label="Cari lokasi TUK"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-14 pl-5">No</TableHead>
                                <TableHead className="w-32">Kode</TableHead>
                                <TableHead>Nama TUK</TableHead>
                                <TableHead className="w-44">Jenis</TableHead>
                                <TableHead className="w-36">Kota</TableHead>
                                <TableHead className="w-28">Status</TableHead>
                                {isAdmin && <TableHead className="w-28 pr-5 text-right">Aksi</TableHead>}
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {tuk.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={isAdmin ? 7 : 6} className="text-muted-foreground py-14 text-center">
                                        {filters.search || filters.status
                                            ? 'Tidak ada lokasi TUK yang cocok dengan filter.'
                                            : 'Belum ada lokasi TUK.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {tuk.data.map((item, index) => (
                                <TableRow key={item.id_tuk}>
                                    <TableCell className="text-muted-foreground pl-5 tabular-nums">{(tuk.from ?? 0) + index}</TableCell>

                                    <TableCell className="font-medium tracking-tight">{item.kode}</TableCell>

                                    <TableCell>
                                        {isAdmin ? (
                                            <Link
                                                href={route('master.tuk.edit', item.id_tuk)}
                                                className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                            >
                                                {item.nama}
                                            </Link>
                                        ) : (
                                            <span className="font-medium">{item.nama}</span>
                                        )}
                                        <p className="text-muted-foreground mt-0.5 text-xs">{item.alamat}</p>
                                    </TableCell>

                                    <TableCell className="text-muted-foreground">{options.jenis[item.jenis] ?? item.jenis}</TableCell>

                                    <TableCell>{item.kota}</TableCell>

                                    <TableCell>
                                        <span
                                            className={cn(
                                                'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset',
                                                statusTone[item.status] ?? 'bg-muted text-muted-foreground ring-border',
                                            )}
                                        >
                                            {options.status[item.status] ?? item.status}
                                        </span>
                                    </TableCell>

                                    {isAdmin && (
                                        <TableCell className="pr-5">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                                    asChild
                                                    title="Ubah TUK"
                                                >
                                                    <Link href={route('master.tuk.edit', item.id_tuk)}>
                                                        <Pencil />
                                                        <span className="sr-only">Ubah {item.nama}</span>
                                                    </Link>
                                                </Button>

                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Hapus TUK"
                                                    className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                    onClick={() => setTarget(item)}
                                                >
                                                    <Trash2 />
                                                    <span className="sr-only">Hapus {item.nama}</span>
                                                </Button>
                                            </div>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {tuk.total > 0 && (
                        <TablePagination
                            from={tuk.from}
                            to={tuk.to}
                            total={tuk.total}
                            prevPageUrl={tuk.prev_page_url}
                            nextPageUrl={tuk.next_page_url}
                        />
                    )}
                </div>
            </div>

            <ConfirmDeleteDialog
                open={target !== null}
                onOpenChange={(open) => !open && setTarget(null)}
                processing={deleting}
                onConfirm={destroy}
                title="Hapus lokasi TUK?"
                description={
                    <>
                        Lokasi <span className="text-foreground font-medium">{target?.nama}</span> ({target?.kode}) akan dihapus dari master data.
                    </>
                }
                note="Data dihapus secara soft delete: tidak tampil lagi di sistem, tetapi tetap tersimpan dan dapat dipulihkan oleh pengelola database."
            />
        </AppLayout>
    );
}
