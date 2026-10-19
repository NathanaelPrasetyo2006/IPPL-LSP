import TablePagination from '@/components/master/table-pagination';
import { StatusPeriodeBadge, StatusPermohonanBadge } from '@/components/pendaftaran/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type StatusPeriode, type StatusPermohonan } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MapPin, Pencil, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

interface Pendaftar {
    id_permohonan: number;
    ulid: string;
    nomor: string;
    kode_asesi: string | null;
    nama: string | null;
    email: string | null;
    no_telp: string | null;
    skema: string | null;
    lokasi: string | null;
    status: StatusPermohonan;
    diajukan_pada: string | null;
}

interface ShowPeriodeProps {
    periode: {
        id_periode: number;
        ulid: string;
        nama: string;
        tanggal_buka: string;
        tanggal_tutup: string;
        kuota: number | null;
        kuota_terpakai: number;
        keterangan: string | null;
        status: StatusPeriode;
        skema: { id_skema: number; ulid: string; judul: string; nomor: string; jumlah_pendaftar: number }[];
        lokasi: { nama: string; alamat: string; skema: string[] }[];
    };
    pendaftar: Paginated<Pendaftar>;
    filters: { skema: string | null; search: string };
}

const SEMUA = 'semua';

function Info({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div>
            <dt className="text-muted-foreground text-xs font-medium tracking-wide uppercase">{label}</dt>
            <dd className="mt-1 text-sm font-medium">{children}</dd>
        </div>
    );
}

export default function ShowPeriode({ periode, pendaftar, filters }: ShowPeriodeProps) {
    const [search, setSearch] = useState(filters.search);
    const isFirstRender = useRef(true);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pendaftaran', href: '/pendaftaran/periode' },
        { title: 'Periode Pendaftaran', href: '/pendaftaran/periode' },
        { title: periode.nama, href: '#' },
    ];

    const visit = (skema: string | null, keyword: string) => {
        const query: Record<string, string> = {};

        if (skema) {
            query.skema = skema;
        }

        if (keyword) {
            query.search = keyword;
        }

        router.get(route('pendaftaran.periode.show', periode.ulid), query, { preserveState: true, preserveScroll: true, replace: true });
    };

    // Pencarian dikirim setelah user berhenti mengetik supaya tidak membanjiri request.
    useEffect(() => {
        if (isFirstRender.current) {
            isFirstRender.current = false;
            return;
        }

        const timeout = setTimeout(() => visit(filters.skema, search), 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const adaFilter = filters.skema !== null || filters.search !== '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={periode.nama} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-muted-foreground text-sm font-medium">Detail periode pendaftaran</p>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">{periode.nama}</h1>
                        <div className="mt-2">
                            <StatusPeriodeBadge status={periode.status} />
                        </div>
                    </div>

                    <div className="flex shrink-0 gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('pendaftaran.periode.index')}>
                                <ArrowLeft />
                                Kembali
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={route('pendaftaran.periode.edit', periode.ulid)}>
                                <Pencil />
                                Ubah
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 xl:grid-cols-[1fr_1fr]">
                    <section className="bg-card rounded-xl border p-5 shadow-xs">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <Info label="Tanggal buka">{periode.tanggal_buka}</Info>
                            <Info label="Tanggal tutup">{periode.tanggal_tutup}</Info>
                            <Info label="Kuota terpakai">
                                <span className="tabular-nums">
                                    {periode.kuota_terpakai} / {periode.kuota ?? 'tanpa batas'}
                                </span>
                            </Info>
                            <Info label="Keterangan">{periode.keterangan || '-'}</Info>
                        </dl>

                        <h2 className="text-muted-foreground mt-6 mb-2 text-xs font-semibold tracking-wide uppercase">Skema dibuka</h2>
                        <ul className="divide-y rounded-lg border">
                            {periode.skema.map((skema) => (
                                <li key={skema.id_skema} className="flex items-center justify-between gap-3 px-3 py-2 text-sm">
                                    <span className="min-w-0">
                                        <span className="block font-medium">{skema.judul}</span>
                                        <span className="text-muted-foreground block text-xs">{skema.nomor}</span>
                                    </span>
                                    <span className="text-muted-foreground shrink-0 text-xs tabular-nums">{skema.jumlah_pendaftar} pendaftar</span>
                                </li>
                            ))}
                        </ul>
                    </section>

                    <section className="bg-card rounded-xl border p-5 shadow-xs">
                        <h2 className="text-muted-foreground mb-2 text-xs font-semibold tracking-wide uppercase">Lokasi TUK</h2>

                        {periode.lokasi.length === 0 ? (
                            <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                                Periode ini belum punya lokasi. Tambahkan lewat tombol Ubah.
                            </p>
                        ) : (
                            <ul className="grid gap-3">
                                {periode.lokasi.map((lokasi) => (
                                    <li key={lokasi.nama} className="rounded-lg border p-3">
                                        <p className="flex items-center gap-1.5 text-sm font-medium">
                                            <MapPin className="text-muted-foreground size-3.5 shrink-0" />
                                            {lokasi.nama}
                                        </p>
                                        <p className="text-muted-foreground mt-0.5 text-xs whitespace-pre-line">{lokasi.alamat}</p>
                                        <div className="mt-2 flex flex-wrap gap-1">
                                            {lokasi.skema.map((judul) => (
                                                <span key={judul} className="bg-muted rounded-md px-2 py-0.5 text-xs">
                                                    {judul}
                                                </span>
                                            ))}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>
                </div>

                <section className="bg-card rounded-xl border shadow-xs">
                    <div className="flex flex-col gap-3 border-b p-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <h2 className="font-semibold">Daftar pendaftar</h2>
                            <p className="text-muted-foreground text-xs">{pendaftar.total} permohonan sesuai filter</p>
                        </div>

                        <div className="flex flex-col gap-2 sm:flex-row">
                            <Select value={filters.skema ?? SEMUA} onValueChange={(value) => visit(value === SEMUA ? null : value, search)}>
                                <SelectTrigger className="sm:w-64" aria-label="Filter skema">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={SEMUA}>Semua skema</SelectItem>
                                    {periode.skema.map((skema) => (
                                        <SelectItem key={skema.id_skema} value={skema.ulid}>
                                            {skema.judul}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <div className="relative sm:w-72">
                                <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                                <Input
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari kode, nama, email, no. HP..."
                                    className="pl-9"
                                    aria-label="Cari pendaftar"
                                />
                            </div>
                        </div>
                    </div>

                    <Table>
                        <TableHeader className="bg-muted/50">
                            <TableRow className="hover:bg-transparent">
                                <TableHead className="pl-5">Kode asesi</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>No. HP</TableHead>
                                <TableHead>Skema</TableHead>
                                <TableHead>Lokasi</TableHead>
                                <TableHead className="pr-5">Status</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {pendaftar.data.length === 0 && (
                                <TableRow className="hover:bg-transparent">
                                    <TableCell colSpan={7} className="text-muted-foreground py-14 text-center">
                                        {adaFilter ? 'Data tidak ditemukan.' : 'Belum ada pendaftar pada periode ini.'}
                                    </TableCell>
                                </TableRow>
                            )}

                            {pendaftar.data.map((item) => (
                                <TableRow key={item.id_permohonan}>
                                    <TableCell className="pl-5 font-mono text-xs">{item.kode_asesi ?? '-'}</TableCell>
                                    <TableCell>
                                        <Link
                                            href={route('pendaftaran.verifikasi.show', item.ulid)}
                                            className="font-medium text-amber-700 hover:underline dark:text-amber-500"
                                        >
                                            {item.nama ?? '-'}
                                        </Link>
                                        <span className="text-muted-foreground block font-mono text-xs">{item.nomor}</span>
                                    </TableCell>
                                    <TableCell className="text-sm">{item.email ?? '-'}</TableCell>
                                    <TableCell className="text-sm tabular-nums">{item.no_telp ?? '-'}</TableCell>
                                    <TableCell className="text-sm whitespace-normal">{item.skema ?? '-'}</TableCell>
                                    <TableCell className="text-sm">
                                        {item.lokasi ?? <span className="text-amber-700 dark:text-amber-500">Belum dipilih</span>}
                                    </TableCell>
                                    <TableCell className="pr-5">
                                        <StatusPermohonanBadge status={item.status} />
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {pendaftar.total > 0 && (
                        <TablePagination
                            from={pendaftar.from}
                            to={pendaftar.to}
                            total={pendaftar.total}
                            prevPageUrl={pendaftar.prev_page_url}
                            nextPageUrl={pendaftar.next_page_url}
                        />
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
