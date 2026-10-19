import StatCard from '@/components/master/stat-card';
import TablePagination from '@/components/master/table-pagination';
import { StatusPermohonanBadge } from '@/components/pendaftaran/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type Paginated, type PermohonanRingkas, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CircleCheck, CircleX, Clock, Search, SearchCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendaftaran', href: '/pendaftaran/verifikasi' },
    { title: 'Verifikasi APL-01', href: '/pendaftaran/verifikasi' },
];

/** periode berisi kode acak (ulid) periode, bukan id. */
type Filter = { status: string; periode: string | null; search: string };

interface IndexProps {
    permohonan: Paginated<PermohonanRingkas>;
    filters: Filter;
    stats: { diajukan: number; diterima: number; ditolak: number; dibatalkan: number };
    periodeOptions: Record<string, string>;
}

const TABS = [
    { value: 'diajukan', label: 'Menunggu' },
    { value: 'diterima', label: 'Diterima' },
    { value: 'ditolak', label: 'Ditolak' },
    { value: 'dibatalkan', label: 'Dibatalkan' },
    { value: 'semua', label: 'Semua' },
];

export default function VerifikasiIndex({ permohonan, filters, stats, periodeOptions }: IndexProps) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    const muat = (perubahan: Partial<Filter>) => {
        const next = { ...filters, search, ...perubahan };

        router.get(
            route('pendaftaran.verifikasi.index'),
            {
                status: next.status,
                ...(next.periode ? { periode: next.periode } : {}),
                ...(next.search ? { search: next.search } : {}),
            },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    // Pencarian dikirim setelah pengguna berhenti mengetik.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => muat({ search }), 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const jumlahTab: Record<string, number> = { ...stats, semua: stats.diajukan + stats.diterima + stats.ditolak + stats.dibatalkan };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verifikasi APL-01" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Verifikasi Permohonan APL-01</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Periksa kelengkapan berkas calon asesi lalu tetapkan status diterima atau ditolak. Asesi otomatis mendapat notifikasi.
                    </p>
                </div>

                {flash.success && (
                    <Alert className="border-emerald-500/40 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-4 md:grid-cols-3">
                    <StatCard icon={Clock} tone="bg-amber-500" value={stats.diajukan} label="Menunggu Verifikasi" />
                    <StatCard icon={CircleCheck} tone="bg-emerald-600" value={stats.diterima} label="Diterima" />
                    <StatCard icon={CircleX} tone="bg-red-600" value={stats.ditolak} label="Ditolak" />
                </div>

                <div className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
                        <div className="bg-muted inline-flex self-start rounded-lg p-1" role="tablist">
                            {TABS.map((tab) => (
                                <button
                                    key={tab.value}
                                    type="button"
                                    role="tab"
                                    aria-selected={filters.status === tab.value}
                                    onClick={() => muat({ status: tab.value })}
                                    className={cn(
                                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                                        filters.status === tab.value ? 'bg-background shadow-xs' : 'text-muted-foreground hover:text-foreground',
                                    )}
                                >
                                    {tab.label}
                                    <span className="text-muted-foreground ml-1.5 text-xs tabular-nums">{jumlahTab[tab.value]}</span>
                                </button>
                            ))}
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Select value={filters.periode ?? 'semua'} onValueChange={(value) => muat({ periode: value === 'semua' ? null : value })}>
                                <SelectTrigger className="h-9 sm:w-56" aria-label="Filter periode">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="semua">Semua periode</SelectItem>
                                    {Object.entries(periodeOptions).map(([id, nama]) => (
                                        <SelectItem key={id} value={id}>
                                            {nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="relative sm:w-64">
                                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari nama, kode asesi, no. APL..."
                                    className="h-9 pl-9"
                                    aria-label="Cari permohonan"
                                />
                            </div>
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="w-40 pl-5">No. Permohonan</TableHead>
                                <TableHead>Calon Asesi</TableHead>
                                <TableHead>Skema</TableHead>
                                <TableHead className="w-44">Diajukan</TableHead>
                                <TableHead className="w-44">Status</TableHead>
                                <TableHead className="w-28 pr-5 text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {permohonan.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={6} className="text-muted-foreground py-14 text-center">
                                        {filters.search
                                            ? 'Tidak ada permohonan yang cocok dengan pencarian.'
                                            : 'Belum ada permohonan pada filter ini.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {permohonan.data.map((item) => (
                                <TableRow key={item.id_permohonan}>
                                    <TableCell className="pl-5 font-mono text-sm">{item.nomor}</TableCell>

                                    <TableCell>
                                        <p className="font-medium">{item.nama_asesi}</p>
                                        <p className="text-muted-foreground font-mono text-xs">{item.kode_asesi}</p>
                                    </TableCell>

                                    <TableCell>
                                        <p className="text-sm">{item.skema?.judul}</p>
                                        <p className="text-muted-foreground text-xs">{item.periode?.nama}</p>
                                    </TableCell>

                                    <TableCell className="text-sm">{item.diajukan_pada}</TableCell>

                                    <TableCell>
                                        <StatusPermohonanBadge status={item.status} />
                                    </TableCell>

                                    <TableCell className="pr-5 text-right">
                                        <Button size="sm" variant={item.status === 'diajukan' ? 'default' : 'outline'} asChild>
                                            <Link href={route('pendaftaran.verifikasi.show', item.ulid)}>
                                                {item.status === 'diajukan' && <SearchCheck />}
                                                {item.status === 'diajukan' ? 'Periksa' : 'Lihat'}
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {permohonan.total > 0 && (
                        <TablePagination
                            from={permohonan.from}
                            to={permohonan.to}
                            total={permohonan.total}
                            prevPageUrl={permohonan.prev_page_url}
                            nextPageUrl={permohonan.next_page_url}
                        />
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
