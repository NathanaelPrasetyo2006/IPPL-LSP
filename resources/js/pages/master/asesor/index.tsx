import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { type AsesorAccount, type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarPlus, ClipboardCheck, Pencil, Plus, Search, Trash2, UserCheck } from 'lucide-react';
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
        akun_bulan_ini: number;
        sedang_bertugas: number;
    };
}

export default function AsesorIndex({ asesor, filters, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const getInitials = useInitials();

    const [search, setSearch] = useState(filters.search);
    const [target, setTarget] = useState<AsesorAccount | null>(null);
    const [deleting, setDeleting] = useState(false);
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Master Data Akun Asesor" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Akun Dasar Asesor</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Buat dan kelola akun login asesor beserta nomor registrasinya.</p>
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
                    <StatCard icon={CalendarPlus} tone="bg-violet-500" value={stats.akun_bulan_ini} label="Dibuat Bulan Ini" />
                    <StatCard icon={ClipboardCheck} tone="bg-emerald-600" value={stats.sedang_bertugas} label="Ditugaskan pada Asesmen" />
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
                                <TableHead className="w-36">Dibuat</TableHead>
                                <TableHead className="w-28 pr-5 text-right">Aksi</TableHead>
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

                            {asesor.data.map((item, index) => (
                                <TableRow key={item.id_asesor} className="group">
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

                                    <TableCell className="text-muted-foreground">{item.dibuat_pada ?? '-'}</TableCell>

                                    <TableCell className="pr-5">
                                        <div className="flex justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                                            <Button variant="ghost" size="icon" className="size-8" asChild title="Ubah akun">
                                                <Link href={route('master.asesor.edit', item.id_asesor)}>
                                                    <Pencil />
                                                    <span className="sr-only">Ubah {item.nama}</span>
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Hapus akun"
                                                className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                onClick={() => setTarget(item)}
                                            >
                                                <Trash2 />
                                                <span className="sr-only">Hapus {item.nama}</span>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
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

            <Dialog open={target !== null} onOpenChange={(open) => !open && setTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus akun asesor?</DialogTitle>
                        <DialogDescription>
                            Akun <span className="font-medium">{target?.nama}</span> ({target?.no_reg}) beserta akun loginnya akan dihapus permanen.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setTarget(null)}>
                            Batal
                        </Button>

                        <Button variant="destructive" onClick={destroy} disabled={deleting}>
                            Hapus
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
