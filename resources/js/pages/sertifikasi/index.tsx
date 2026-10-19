import { StatusPermohonanBadge } from '@/components/pendaftaran/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PermohonanRingkas, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BadgeCheck, CalendarRange, CircleCheck, FileText, Inbox, Info, MapPinOff, PencilLine } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pendaftaran Sertifikasi', href: '/sertifikasi' }];

interface PeriodeDibuka {
    id_periode: number;
    ulid: string;
    nama: string;
    keterangan: string | null;
    tanggal_buka: string;
    tanggal_tutup: string;
    kuota_penuh: boolean;
    /** Judul skema yang sudah didaftarkan pada periode ini (satu periode satu skema). */
    skema_terdaftar: string | null;
    skema: {
        id_skema: number;
        ulid: string;
        judul: string;
        nomor: string;
        unit_kompetensi_count: number;
        keadaan: 'daftar' | 'terdaftar' | 'terkunci' | 'perbaiki';
        permohonan_ulid: string | null;
    }[];
}

interface IndexProps {
    kodeAsesi: string | null;
    periode: PeriodeDibuka[];
    permohonan: PermohonanRingkas[];
}

export default function SertifikasiIndex({ kodeAsesi, periode, permohonan }: IndexProps) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pendaftaran Sertifikasi" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Pendaftaran Sertifikasi</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Pilih skema pada periode yang sedang dibuka, lalu isi Formulir Permohonan Sertifikasi (FR-APL-01).
                        </p>
                    </div>

                    {kodeAsesi && (
                        <span className="bg-muted inline-flex shrink-0 items-center gap-2 self-start rounded-lg px-3 py-1.5 text-sm">
                            <span className="text-muted-foreground">Kode asesi</span>
                            <span className="font-mono font-semibold">{kodeAsesi}</span>
                        </span>
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

                <section className="grid gap-4">
                    <h2 className="text-lg font-semibold">Periode yang dibuka</h2>

                    {periode.length === 0 ? (
                        <div className="bg-card flex flex-col items-center gap-3 rounded-xl border px-6 py-12 text-center shadow-xs">
                            <span className="bg-muted text-muted-foreground flex size-12 items-center justify-center rounded-full">
                                <CalendarRange className="size-6" />
                            </span>
                            <div>
                                <p className="font-medium">Belum ada periode pendaftaran yang dibuka</p>
                                <p className="text-muted-foreground mt-1 text-sm">Pantau halaman ini secara berkala atau hubungi admin LSP.</p>
                            </div>
                        </div>
                    ) : (
                        periode.map((item) => (
                            <div key={item.id_periode} className="bg-card rounded-xl border shadow-xs">
                                <div className="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <h3 className="font-semibold">{item.nama}</h3>
                                        <p className="text-muted-foreground text-sm">
                                            Pendaftaran {item.tanggal_buka} –{' '}
                                            <span className="text-foreground font-medium">{item.tanggal_tutup}</span>
                                        </p>
                                    </div>

                                    {item.kuota_penuh && (
                                        <span className="self-start rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-600/20 ring-inset dark:bg-red-950/50 dark:text-red-400">
                                            Kuota penuh
                                        </span>
                                    )}
                                </div>

                                {item.keterangan && (
                                    <p className="text-muted-foreground border-b px-5 py-3 text-sm whitespace-pre-line">{item.keterangan}</p>
                                )}

                                {item.skema_terdaftar && (
                                    <p className="flex items-start gap-2 border-b bg-sky-50/60 px-5 py-3 text-sm text-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                                        <Info className="mt-0.5 size-4 shrink-0" />
                                        <span>
                                            Anda sudah mendaftar skema <span className="font-medium">{item.skema_terdaftar}</span>. Satu periode hanya
                                            untuk satu skema; batalkan permohonan tersebut (selama belum diverifikasi) bila salah memilih skema.
                                        </span>
                                    </p>
                                )}

                                <ul className="divide-y">
                                    {item.skema.map((skema) => (
                                        <li key={skema.id_skema} className="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                                                <BadgeCheck className="size-5" />
                                            </span>

                                            <div className="min-w-0 flex-1">
                                                <p className="font-medium">{skema.judul}</p>
                                                <p className="text-muted-foreground text-xs">
                                                    {skema.nomor} · {skema.unit_kompetensi_count} unit kompetensi
                                                </p>
                                            </div>

                                            {skema.keadaan === 'terdaftar' ? (
                                                <Link
                                                    href={route('sertifikasi.show', skema.permohonan_ulid ?? '')}
                                                    className="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                                                >
                                                    <CircleCheck className="size-4" />
                                                    Sudah mendaftar
                                                </Link>
                                            ) : skema.keadaan === 'terkunci' ? (
                                                <Button size="sm" disabled title="Satu periode hanya untuk satu skema">
                                                    Daftar
                                                </Button>
                                            ) : skema.keadaan === 'perbaiki' ? (
                                                <Button asChild size="sm" variant="outline">
                                                    <Link href={route('sertifikasi.perbaiki', skema.permohonan_ulid ?? '')}>
                                                        <PencilLine />
                                                        Perbaiki permohonan
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <Button asChild={!item.kuota_penuh} disabled={item.kuota_penuh} size="sm">
                                                    {item.kuota_penuh ? (
                                                        <span>Daftar</span>
                                                    ) : (
                                                        <Link href={route('sertifikasi.create', [item.ulid, skema.ulid])}>
                                                            Daftar
                                                            <ArrowRight />
                                                        </Link>
                                                    )}
                                                </Button>
                                            )}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        ))
                    )}
                </section>

                <section className="grid gap-4">
                    <h2 className="text-lg font-semibold">Permohonan saya</h2>

                    {permohonan.length === 0 ? (
                        <div className="bg-card text-muted-foreground flex items-center gap-3 rounded-xl border px-5 py-6 text-sm shadow-xs">
                            <Inbox className="size-5 shrink-0" />
                            Anda belum pernah mengajukan permohonan sertifikasi.
                        </div>
                    ) : (
                        <ul className="bg-card divide-y rounded-xl border shadow-xs">
                            {permohonan.map((item) => (
                                <li key={item.id_permohonan}>
                                    <Link
                                        href={route('sertifikasi.show', item.ulid)}
                                        className="hover:bg-muted/40 flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center"
                                    >
                                        <span className="bg-muted text-muted-foreground flex size-10 shrink-0 items-center justify-center rounded-lg">
                                            <FileText className="size-5" />
                                        </span>

                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium">{item.skema?.judul}</p>
                                            <p className="text-muted-foreground text-xs">
                                                <span className="font-mono">{item.nomor}</span> · {item.periode?.nama}
                                                {item.lokasi && ` · ${item.lokasi.nama}`} · diajukan {item.diajukan_pada}
                                            </p>
                                        </div>

                                        {item.perlu_pilih_lokasi && (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20 ring-inset dark:bg-amber-950/50 dark:text-amber-400">
                                                <MapPinOff className="size-3" />
                                                Pilih ulang lokasi
                                            </span>
                                        )}

                                        <StatusPermohonanBadge status={item.status} />
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>
        </AppLayout>
    );
}
