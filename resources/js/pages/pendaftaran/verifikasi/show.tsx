import InputError from '@/components/input-error';
import { DataPemohon, DataSertifikasi, DokumenList, Panel, RiwayatPermohonan } from '@/components/pendaftaran/permohonan-detail';
import { StatusPermohonanBadge } from '@/components/pendaftaran/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type PermohonanDetail, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CircleCheck, CircleX, ClipboardCheck, LoaderCircle, TriangleAlert } from 'lucide-react';
import { FormEventHandler } from 'react';

type VerifikasiForm = {
    keputusan: '' | 'diterima' | 'ditolak';
    catatan: string;
    cek_dokumen: Record<string, boolean>;
};

const textareaClass =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-24 w-full rounded-md border px-3 py-2 text-base focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden md:text-sm';

function CekToggle({ value, onChange, disabled }: { value: boolean | undefined; onChange: (value: boolean) => void; disabled?: boolean }) {
    return (
        <div className="bg-muted inline-flex shrink-0 rounded-lg p-0.5" role="radiogroup">
            <button
                type="button"
                role="radio"
                aria-checked={value === true}
                disabled={disabled}
                onClick={() => onChange(true)}
                className={cn(
                    'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium',
                    value === true ? 'bg-emerald-600 text-white shadow-xs' : 'text-muted-foreground hover:text-foreground',
                )}
            >
                <CircleCheck className="size-3.5" />
                Memenuhi
            </button>
            <button
                type="button"
                role="radio"
                aria-checked={value === false}
                disabled={disabled}
                onClick={() => onChange(false)}
                className={cn(
                    'inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium',
                    value === false ? 'bg-red-600 text-white shadow-xs' : 'text-muted-foreground hover:text-foreground',
                )}
            >
                <CircleX className="size-3.5" />
                Tidak
            </button>
        </div>
    );
}

export default function VerifikasiShow({ permohonan }: { permohonan: PermohonanDetail }) {
    const { flash } = usePage<SharedData>().props;
    const sudahDiputuskan = permohonan.status !== 'diajukan';

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pendaftaran', href: '/pendaftaran/verifikasi' },
        { title: 'Verifikasi APL-01', href: '/pendaftaran/verifikasi' },
        { title: permohonan.nomor, href: '#' },
    ];

    // Mulai kosong supaya setiap dokumen benar-benar diperiksa; yang tidak ditandai dianggap tidak memenuhi.
    const { data, setData, put, errors, processing } = useForm<VerifikasiForm>({
        keputusan: '',
        catatan: '',
        cek_dokumen: {},
    });

    const wajibBelumMemenuhi = permohonan.dokumen.filter((d) => d.wajib && !(d.ada && data.cek_dokumen[d.jenis]));
    const dokumenAda = permohonan.dokumen.filter((d) => d.ada);
    const semuaMemenuhi = () => setData('cek_dokumen', Object.fromEntries(permohonan.dokumen.map((d) => [d.jenis, d.ada])));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('pendaftaran.verifikasi.update', permohonan.ulid), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Verifikasi ${permohonan.nomor}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-muted-foreground font-mono text-sm">
                            {permohonan.nomor} · {permohonan.pemohon.kode_asesi}
                        </p>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">{permohonan.pemohon.nama_lengkap}</h1>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-2 text-sm">
                            <StatusPermohonanBadge status={permohonan.status} />
                            <span>
                                {permohonan.skema.judul} · diajukan {permohonan.diajukan_pada}
                            </span>
                        </div>
                    </div>

                    <Button variant="outline" asChild className="shrink-0">
                        <Link href={route('pendaftaran.verifikasi.index')}>
                            <ArrowLeft />
                            Kembali
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
                        <TriangleAlert className="size-4" />
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 xl:grid-cols-[1fr_420px]">
                    <div className="grid content-start gap-6">
                        <Panel
                            title="Bagian 3 · Bukti Kelengkapan"
                            description={sudahDiputuskan ? 'Hasil pemeriksaan dokumen.' : 'Buka setiap dokumen lalu tandai memenuhi atau tidak.'}
                        >
                            <DokumenList
                                dokumen={permohonan.dokumen}
                                cek={sudahDiputuskan ? permohonan.cek_dokumen : undefined}
                                aksi={
                                    sudahDiputuskan
                                        ? undefined
                                        : (dokumen) =>
                                              dokumen.ada ? (
                                                  <CekToggle
                                                      value={data.cek_dokumen[dokumen.jenis]}
                                                      onChange={(value) => setData('cek_dokumen', { ...data.cek_dokumen, [dokumen.jenis]: value })}
                                                  />
                                              ) : (
                                                  dokumen.wajib && (
                                                      <span className="text-xs font-medium text-red-600 dark:text-red-400">Belum diunggah</span>
                                                  )
                                              )
                                }
                            />
                        </Panel>

                        <DataPemohon pemohon={permohonan.pemohon} />
                        <DataSertifikasi permohonan={permohonan} />
                        <RiwayatPermohonan riwayat={permohonan.riwayat_status} />
                    </div>

                    <div className="xl:sticky xl:top-4 xl:self-start">
                        {sudahDiputuskan ? (
                            <Panel title="Hasil verifikasi">
                                <div className="grid gap-3 text-sm">
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Keputusan</span>
                                        <StatusPermohonanBadge status={permohonan.status} />
                                    </div>
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-muted-foreground">Diverifikasi</span>
                                        <span className="text-right font-medium">{permohonan.diverifikasi_pada}</span>
                                    </div>
                                    <div className="flex items-center justify-between gap-4">
                                        <span className="text-muted-foreground">Oleh</span>
                                        <span className="truncate text-right font-medium">{permohonan.verifikator ?? '-'}</span>
                                    </div>
                                    <div>
                                        <span className="text-muted-foreground">Catatan</span>
                                        <p className="bg-muted/50 mt-1 rounded-lg p-3 whitespace-pre-line">{permohonan.catatan_verifikasi || '-'}</p>
                                    </div>
                                </div>
                            </Panel>
                        ) : (
                            <form onSubmit={submit} className="bg-card rounded-xl border shadow-xs">
                                <div className="border-b px-5 py-4">
                                    <h2 className="flex items-center gap-2 font-semibold">
                                        <ClipboardCheck className="size-4" />
                                        Keputusan verifikasi
                                    </h2>
                                    <p className="text-muted-foreground mt-0.5 text-sm">Rekomendasi LSP atas permohonan ini.</p>
                                </div>

                                <div className="grid gap-5 p-5">
                                    <div className="flex items-center justify-between gap-3 rounded-lg border p-3 text-sm">
                                        <span>
                                            <span className="font-medium tabular-nums">
                                                {dokumenAda.filter((d) => data.cek_dokumen[d.jenis]).length}/{dokumenAda.length}
                                            </span>{' '}
                                            dokumen memenuhi
                                        </span>
                                        <button
                                            type="button"
                                            onClick={semuaMemenuhi}
                                            className="text-muted-foreground hover:text-foreground text-xs underline"
                                        >
                                            Tandai semua memenuhi
                                        </button>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label>Keputusan</Label>
                                        <div className="grid grid-cols-2 gap-2">
                                            {(['diterima', 'ditolak'] as const).map((value) => (
                                                <button
                                                    key={value}
                                                    type="button"
                                                    onClick={() => setData('keputusan', value)}
                                                    aria-pressed={data.keputusan === value}
                                                    className={cn(
                                                        'flex items-center justify-center gap-2 rounded-lg border px-3 py-3 text-sm font-medium transition-colors',
                                                        data.keputusan === value &&
                                                            value === 'diterima' &&
                                                            'border-emerald-600 bg-emerald-600 text-white',
                                                        data.keputusan === value && value === 'ditolak' && 'border-red-600 bg-red-600 text-white',
                                                        data.keputusan !== value && 'hover:bg-muted',
                                                    )}
                                                >
                                                    {value === 'diterima' ? <CircleCheck className="size-4" /> : <CircleX className="size-4" />}
                                                    {value === 'diterima' ? 'Diterima' : 'Ditolak'}
                                                </button>
                                            ))}
                                        </div>
                                        <InputError message={errors.keputusan} />

                                        {data.keputusan === 'diterima' && wajibBelumMemenuhi.length > 0 && (
                                            <p className="text-xs text-amber-700 dark:text-amber-400">
                                                Dokumen wajib belum ditandai memenuhi: {wajibBelumMemenuhi.map((d) => d.label).join(', ')}.
                                            </p>
                                        )}
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="catatan">
                                            Catatan untuk asesi
                                            {data.keputusan === 'ditolak' && <span className="text-red-600"> *</span>}
                                        </Label>
                                        <textarea
                                            id="catatan"
                                            rows={4}
                                            value={data.catatan}
                                            onChange={(e) => setData('catatan', e.target.value)}
                                            required={data.keputusan === 'ditolak'}
                                            className={textareaClass}
                                            placeholder={
                                                data.keputusan === 'ditolak'
                                                    ? 'Jelaskan dokumen mana yang belum sesuai dan apa yang perlu diperbaiki.'
                                                    : 'Opsional, mis. informasi lanjutan untuk asesi.'
                                            }
                                        />
                                        <p className="text-muted-foreground text-xs">Catatan ikut dikirim di email dan notifikasi ke asesi.</p>
                                        <InputError message={errors.catatan} />
                                    </div>
                                </div>

                                <div className="bg-muted/30 rounded-b-xl border-t px-5 py-4">
                                    <Button type="submit" className="w-full" disabled={processing || data.keputusan === ''}>
                                        {processing && <LoaderCircle className="animate-spin" />}
                                        Simpan & kirim notifikasi
                                    </Button>
                                </div>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
