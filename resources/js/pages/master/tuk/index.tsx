import ConfirmDeleteDialog from '@/components/master/confirm-delete-dialog';
import TablePagination from '@/components/master/table-pagination';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useRole } from '@/hooks/use-role';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData, type Tuk } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
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
    filters: { search: string };
}

export default function TukIndex({ tuk, filters }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const { isAdmin } = useRole();

    const [search, setSearch] = useState(filters.search);
    const [target, setTarget] = useState<Tuk | null>(null);
    const [deleting, setDeleting] = useState(false);
    const isFirstRender = useRef(true);

    // Pencarian dikirim setelah user berhenti mengetik supaya tidak membanjiri request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => {
            router.get(route('master.tuk.index'), search ? { search } : {}, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    const destroy = () => {
        if (!target) {
            return;
        }

        router.delete(route('master.tuk.destroy', target.ulid), {
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
            <Head title="Master Data Lokasi TUK" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Master Data Lokasi TUK</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {isAdmin ? 'Kelola daftar lokasi Tempat Uji Kompetensi.' : 'Daftar lokasi Tempat Uji Kompetensi.'}
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

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground text-sm">
                            Total <span className="text-foreground font-medium">{tuk.total}</span> lokasi TUK
                        </p>

                        <div className="relative sm:w-72">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari nama atau alamat..."
                                className="pl-9"
                                aria-label="Cari lokasi TUK"
                            />
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-14 pl-5">No</TableHead>
                                <TableHead className="w-72">Nama Lokasi</TableHead>
                                <TableHead>Detail Alamat</TableHead>
                                {isAdmin && <TableHead className="w-28 pr-5 text-right">Aksi</TableHead>}
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {tuk.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={isAdmin ? 4 : 3} className="text-muted-foreground py-14 text-center">
                                        {filters.search ? 'Tidak ada lokasi TUK yang cocok dengan pencarian.' : 'Belum ada lokasi TUK.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {tuk.data.map((item, index) => (
                                <TableRow key={item.id_tuk}>
                                    <TableCell className="text-muted-foreground pl-5 align-top tabular-nums">{(tuk.from ?? 0) + index}</TableCell>

                                    <TableCell className="align-top whitespace-normal">
                                        {isAdmin ? (
                                            <Link
                                                href={route('master.tuk.edit', item.ulid)}
                                                className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                            >
                                                {item.nama}
                                            </Link>
                                        ) : (
                                            <span className="font-medium">{item.nama}</span>
                                        )}
                                    </TableCell>

                                    <TableCell className="text-muted-foreground align-top whitespace-pre-line">{item.alamat}</TableCell>

                                    {isAdmin && (
                                        <TableCell className="pr-5 align-top">
                                            <div className="flex justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    className="size-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                                    asChild
                                                    title="Ubah TUK"
                                                >
                                                    <Link href={route('master.tuk.edit', item.ulid)}>
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
                        Lokasi <span className="text-foreground font-medium">{target?.nama}</span> akan dihapus dari master data.
                    </>
                }
                note="Data dihapus secara soft delete: tidak tampil lagi di sistem, tetapi tetap tersimpan dan dapat dipulihkan oleh pengelola database."
            />
        </AppLayout>
    );
}
