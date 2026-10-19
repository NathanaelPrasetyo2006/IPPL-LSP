import ConfirmDeleteDialog from '@/components/master/confirm-delete-dialog';
import SkemaDetailDialog from '@/components/master/skema-detail-dialog';
import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import UnitKompetensiDialog from '@/components/master/unit-kompetensi-dialog';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useRole } from '@/hooks/use-role';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData, type SkemaSertifikasi, type UnitKompetensi } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { BadgeCheck, Eye, FileStack, LayoutList, ListPlus, Pencil, Plus, Search, Trash2 } from 'lucide-react';
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

const actionClass = {
    view: 'text-sky-600 hover:bg-sky-50 hover:text-sky-700 dark:text-sky-400 dark:hover:bg-sky-950/50',
    addUnit: 'text-violet-600 hover:bg-violet-50 hover:text-violet-700 dark:text-violet-400 dark:hover:bg-violet-950/50',
    edit: 'text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50',
    delete: 'text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50',
};

export default function SkemaSertifikasiIndex({ skema, filters, stats }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const { isAdmin } = useRole();

    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    // Yang disimpan hanya id; datanya selalu diambil dari props terbaru supaya
    // daftar unit di popup ikut berubah setelah tambah/ubah/susun unit.
    const [detailId, setDetailId] = useState<number | null>(null);
    const [unitDialog, setUnitDialog] = useState<{ skemaId: number; unit: UnitKompetensi | null } | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<SkemaSertifikasi | null>(null);
    const [deleting, setDeleting] = useState(false);

    const findSkema = (id: number | undefined) => skema.data.find((item) => item.id_skema === id) ?? null;
    const detailSkema = findSkema(detailId ?? undefined);
    const unitSkema = findSkema(unitDialog?.skemaId);

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
        if (!deleteTarget) {
            return;
        }

        router.delete(route('master.skema-sertifikasi.destroy', deleteTarget.id_skema), {
            preserveScroll: true,
            onStart: () => setDeleting(true),
            onFinish: () => {
                setDeleting(false);
                setDeleteTarget(null);
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
                        <p className="text-muted-foreground mt-1 text-sm">
                            {isAdmin
                                ? 'Kelola dan pantau seluruh skema sertifikasi kompetensi yang tersedia.'
                                : 'Daftar skema sertifikasi beserta unit kompetensinya sebagai acuan asesmen.'}
                        </p>
                    </div>

                    {isAdmin && (
                        <Button asChild className="shrink-0">
                            <Link href={route('master.skema-sertifikasi.create')}>
                                <Plus />
                                Tambah Skema
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
                    <StatCard icon={BadgeCheck} tone="bg-blue-600" value={stats.total_skema} label="Total Skema Sertifikasi" />
                    <StatCard icon={LayoutList} tone="bg-violet-500" value={stats.total_unit} label="Total Unit Kompetensi" />
                    <StatCard icon={FileStack} tone="bg-emerald-600" value={stats.skema_tanpa_unit} label="Skema Belum Punya Unit Kompetensi" />
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
                                <TableHead className="w-36 text-center">Unit Kompetensi</TableHead>
                                <TableHead className={isAdmin ? 'w-44 pr-5 text-right' : 'w-20 pr-5 text-right'}>Aksi</TableHead>
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
                                <TableRow key={item.id_skema}>
                                    <TableCell className="text-muted-foreground pl-5 tabular-nums">{(skema.from ?? 0) + index}</TableCell>

                                    <TableCell className="font-medium tracking-tight">{item.nomor}</TableCell>

                                    <TableCell>
                                        <button
                                            type="button"
                                            onClick={() => setDetailId(item.id_skema)}
                                            className="text-left font-medium text-amber-700 hover:underline dark:text-amber-500"
                                        >
                                            {item.judul}
                                        </button>
                                    </TableCell>

                                    <TableCell className="text-center tabular-nums">{item.unit_kompetensi_count ?? 0}</TableCell>

                                    <TableCell className="pr-5">
                                        <div className="flex justify-end gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className={`size-8 ${actionClass.view}`}
                                                title="Lihat detail"
                                                onClick={() => setDetailId(item.id_skema)}
                                            >
                                                <Eye />
                                                <span className="sr-only">Lihat detail {item.judul}</span>
                                            </Button>

                                            {isAdmin && (
                                                <>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className={`size-8 ${actionClass.addUnit}`}
                                                        title="Tambah unit kompetensi"
                                                        onClick={() => setUnitDialog({ skemaId: item.id_skema, unit: null })}
                                                    >
                                                        <ListPlus />
                                                        <span className="sr-only">Tambah unit kompetensi ke {item.judul}</span>
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        className={`size-8 ${actionClass.edit}`}
                                                        asChild
                                                        title="Ubah skema"
                                                    >
                                                        <Link href={route('master.skema-sertifikasi.edit', item.id_skema)}>
                                                            <Pencil />
                                                            <span className="sr-only">Ubah {item.judul}</span>
                                                        </Link>
                                                    </Button>

                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        title="Hapus skema"
                                                        className={`size-8 ${actionClass.delete}`}
                                                        onClick={() => setDeleteTarget(item)}
                                                    >
                                                        <Trash2 />
                                                        <span className="sr-only">Hapus {item.judul}</span>
                                                    </Button>
                                                </>
                                            )}
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {skema.total > 0 && (
                        <TablePagination
                            from={skema.from}
                            to={skema.to}
                            total={skema.total}
                            prevPageUrl={skema.prev_page_url}
                            nextPageUrl={skema.next_page_url}
                        />
                    )}
                </div>
            </div>

            <SkemaDetailDialog
                skema={detailSkema}
                onOpenChange={(open) => !open && setDetailId(null)}
                canManage={isAdmin}
                onAddUnit={() => detailSkema && setUnitDialog({ skemaId: detailSkema.id_skema, unit: null })}
                onEditUnit={(unit) => setUnitDialog({ skemaId: unit.id_skema, unit })}
            />

            {isAdmin && (
                <>
                    <UnitKompetensiDialog
                        open={unitDialog !== null}
                        onOpenChange={(open) => !open && setUnitDialog(null)}
                        skema={unitSkema}
                        unit={unitDialog?.unit ?? null}
                    />

                    <ConfirmDeleteDialog
                        open={deleteTarget !== null}
                        onOpenChange={(open) => !open && setDeleteTarget(null)}
                        processing={deleting}
                        onConfirm={destroy}
                        title="Hapus skema sertifikasi?"
                        description={
                            <>
                                Skema <span className="text-foreground font-medium">{deleteTarget?.nomor}</span> &mdash; {deleteTarget?.judul} akan
                                dihapus dari daftar beserta unit kompetensinya.
                            </>
                        }
                        note="Data dihapus secara soft delete: tidak tampil lagi di sistem, tetapi tetap tersimpan dan dapat dipulihkan oleh pengelola database."
                    />
                </>
            )}
        </AppLayout>
    );
}
