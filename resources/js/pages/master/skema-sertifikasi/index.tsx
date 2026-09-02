import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Paginated, type SharedData, type SkemaSertifikasi } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { BadgeCheck, ChevronLeft, ChevronRight, FileStack, LayoutList, type LucideIcon, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/skema-sertifikasi',
    },
    {
        title: 'Skema Sertifikasi',
        href: '/master/skema-sertifikasi',
    },
];

interface IndexProps {
    skema: Paginated<SkemaSertifikasi>;
    filters: { search: string };
    stats: {
        total_skema: number;
        total_unit: number;
        skema_tanpa_unit: number;
    };
}

function StatCard({ icon: Icon, tone, value, label }: { icon: LucideIcon; tone: string; value: number; label: string }) {
    return (
        <div className="bg-card rounded-xl border p-5 shadow-xs">
            <div className={cn('mb-5 flex size-11 items-center justify-center rounded-xl text-white', tone)}>
                <Icon className="size-5" />
            </div>

            <p className="text-3xl font-bold tracking-tight tabular-nums">{value}</p>
            <p className="text-muted-foreground mt-1 text-sm">{label}</p>
        </div>
    );
}

export default function SkemaSertifikasiIndex({ skema, filters, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;

    const [search, setSearch] = useState(filters.search);
    const [target, setTarget] = useState<SkemaSertifikasi | null>(null);
    const [deleting, setDeleting] = useState(false);
    const isFirstRender = useRef(true);

    // Pencarian dikirim setelah user berhenti mengetik supaya tidak membanjiri request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('master.skema-sertifikasi.index'), search ? { search } : {}, {
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

        router.delete(route('master.skema-sertifikasi.destroy', target.id_skema), {
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
            <Head title="Master Data Skema Sertifikasi" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Master Data Skema Sertifikasi</h1>
                        <p className="text-muted-foreground mt-1 text-sm">Kelola dan pantau seluruh skema sertifikasi kompetensi yang tersedia.</p>
                    </div>

                    <Button asChild className="shrink-0">
                        <Link href={route('master.skema-sertifikasi.create')}>
                            <Plus />
                            Tambah Skema
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
                    <StatCard icon={BadgeCheck} tone="bg-blue-600" value={stats.total_skema} label="Total Skema Sertifikasi" />
                    <StatCard icon={LayoutList} tone="bg-violet-500" value={stats.total_unit} label="Total Unit Kompetensi" />
                    <StatCard icon={FileStack} tone="bg-emerald-600" value={stats.skema_tanpa_unit} label="Skema Belum Punya Unit" />
                </div>

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <h2 className="text-base font-semibold">Daftar Skema Sertifikasi</h2>

                        <div className="relative sm:w-72">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari skema..."
                                className="pl-9"
                                aria-label="Cari skema sertifikasi"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-14 pl-5">No</TableHead>
                                <TableHead className="w-56">Kode Skema</TableHead>
                                <TableHead>Nama Skema Sertifikasi</TableHead>
                                <TableHead className="w-40 text-center">Unit Kompetensi</TableHead>
                                <TableHead className="w-28 pr-5 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {skema.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={5} className="text-muted-foreground py-14 text-center">
                                        {filters.search ? 'Tidak ada skema yang cocok dengan pencarian.' : 'Belum ada skema sertifikasi.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {skema.data.map((item, index) => (
                                <TableRow key={item.id_skema} className="group">
                                    <TableCell className="text-muted-foreground pl-5 tabular-nums">{(skema.from ?? 0) + index}</TableCell>

                                    <TableCell className="font-medium tracking-tight">{item.nomor}</TableCell>

                                    <TableCell>
                                        <Link
                                            href={route('master.skema-sertifikasi.edit', item.id_skema)}
                                            className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                        >
                                            {item.judul}
                                        </Link>
                                    </TableCell>

                                    <TableCell className="text-center tabular-nums">{item.unit_kompetensi_count ?? 0}</TableCell>

                                    <TableCell className="pr-5">
                                        <div className="flex justify-end gap-1 opacity-0 transition-opacity group-hover:opacity-100 focus-within:opacity-100">
                                            <Button variant="ghost" size="icon" className="size-8" asChild title="Ubah skema">
                                                <Link href={route('master.skema-sertifikasi.edit', item.id_skema)}>
                                                    <Pencil />
                                                    <span className="sr-only">Ubah {item.judul}</span>
                                                </Link>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                title="Hapus skema"
                                                className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                onClick={() => setTarget(item)}
                                            >
                                                <Trash2 />
                                                <span className="sr-only">Hapus {item.judul}</span>
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {skema.total > 0 && (
                        <div className="flex items-center justify-between border-t p-4">
                            <p className="text-muted-foreground text-sm">
                                Menampilkan {skema.from ?? 0}&ndash;{skema.to ?? 0} dari {skema.total} data
                            </p>

                            <div className="flex gap-2">
                                {skema.prev_page_url ? (
                                    <Button variant="outline" size="icon" className="size-9" asChild>
                                        <Link href={skema.prev_page_url} preserveScroll preserveState>
                                            <ChevronLeft />
                                            <span className="sr-only">Halaman sebelumnya</span>
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" size="icon" className="size-9" disabled>
                                        <ChevronLeft />
                                    </Button>
                                )}

                                {skema.next_page_url ? (
                                    <Button variant="outline" size="icon" className="size-9" asChild>
                                        <Link href={skema.next_page_url} preserveScroll preserveState>
                                            <ChevronRight />
                                            <span className="sr-only">Halaman berikutnya</span>
                                        </Link>
                                    </Button>
                                ) : (
                                    <Button variant="outline" size="icon" className="size-9" disabled>
                                        <ChevronRight />
                                    </Button>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Dialog open={target !== null} onOpenChange={(open) => !open && setTarget(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Hapus skema sertifikasi?</DialogTitle>
                        <DialogDescription>
                            Skema <span className="font-medium">{target?.nomor}</span> beserta seluruh unit kompetensi, elemen, dan pertanyaan di
                            bawahnya akan ikut terhapus permanen.
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
