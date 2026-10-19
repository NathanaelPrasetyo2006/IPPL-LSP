import InputError from '@/components/input-error';
import { DataPemohon, DataSertifikasi, DokumenList, Panel, RiwayatPermohonan } from '@/components/pendaftaran/permohonan-detail';
import PilihLokasi from '@/components/pendaftaran/pilih-lokasi';
import { StatusPermohonanBadge } from '@/components/pendaftaran/status-badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type PermohonanDetail, type SharedData, type Tuk } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, CircleCheck, CircleX, Clock, LoaderCircle, MapPinOff, PencilLine } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ShowProps {
    permohonan: PermohonanDetail;
    /** Permohonan ditolak yang masih bisa diperbaiki dan diajukan lagi. */
    bisaPerbaiki: boolean;
    /** Masih menunggu verifikasi, jadi asesi boleh membatalkannya sendiri. */
    bisaBatal: boolean;
    /** Lokasi yang bisa dipilih bila lokasi sebelumnya dihapus admin. */
    lokasiTersedia: Tuk[];
}

const HASIL = {
    diajukan: {
        icon: Clock,
        judul: 'Menunggu verifikasi',
        pesan: 'Berkas Anda sedang diperiksa admin LSP. Hasilnya akan dikirim ke email Anda dan muncul di ikon lonceng.',
        className: 'border-amber-500/40 bg-amber-50 text-amber-900 dark:bg-amber-950/40 dark:text-amber-200',
        iconClass: 'bg-amber-500',
    },
    diterima: {
        icon: CircleCheck,
        judul: 'Permohonan diterima',
        pesan: 'Selamat! Anda diterima sebagai peserta sertifikasi. Informasi jadwal asesmen akan disampaikan selanjutnya.',
        className: 'border-emerald-500/40 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200',
        iconClass: 'bg-emerald-600',
    },
    ditolak: {
        icon: CircleX,
        judul: 'Permohonan ditolak',
        pesan: 'Berkas Anda belum memenuhi persyaratan. Perbaiki sesuai catatan verifikator lalu ajukan ulang permohonan ini.',
        className: 'border-red-500/40 bg-red-50 text-red-900 dark:bg-red-950/40 dark:text-red-200',
        iconClass: 'bg-red-600',
    },
    dibatalkan: {
        icon: Ban,
        judul: 'Permohonan dibatalkan',
        pesan: 'Permohonan ini sudah Anda batalkan dan tidak diperiksa admin. Silakan daftar skema lain bila periode masih dibuka.',
        className: 'border-slate-400/40 bg-slate-50 text-slate-800 dark:bg-slate-900/40 dark:text-slate-200',
        iconClass: 'bg-slate-500',
    },
};

export default function ShowPermohonan({ permohonan, bisaPerbaiki, bisaBatal, lokasiTersedia }: ShowProps) {
    const { flash } = usePage<SharedData>().props;
    const hasil = HASIL[permohonan.status];

    const [konfirmasiBatal, setKonfirmasiBatal] = useState(false);
    const [membatalkan, setMembatalkan] = useState(false);

    const batalkan = () => {
        router.post(route('sertifikasi.batal', permohonan.ulid), {}, { onStart: () => setMembatalkan(true), onFinish: () => setMembatalkan(false) });
    };

    const lokasiForm = useForm({ id_tuk: lokasiTersedia.length === 1 ? lokasiTersedia[0].id_tuk.toString() : '' });

    const simpanLokasi: FormEventHandler = (e) => {
        e.preventDefault();

        lokasiForm.put(route('sertifikasi.lokasi', permohonan.ulid), { preserveScroll: true });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Pendaftaran Sertifikasi', href: '/sertifikasi' },
        { title: permohonan.nomor, href: '#' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Permohonan ${permohonan.nomor}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p className="text-muted-foreground font-mono text-sm">{permohonan.nomor}</p>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">{permohonan.skema.judul}</h1>
                        <div className="text-muted-foreground mt-2 flex flex-wrap items-center gap-2 text-sm">
                            <StatusPermohonanBadge status={permohonan.status} />
                            <span>Diajukan {permohonan.diajukan_pada}</span>
                        </div>
                    </div>

                    <Button variant="outline" asChild className="shrink-0">
                        <Link href={route('sertifikasi.index')}>
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
                        <AlertDescription>{flash.error}</AlertDescription>
                    </Alert>
                )}

                {permohonan.perlu_pilih_lokasi && (
                    <form
                        onSubmit={simpanLokasi}
                        className="flex flex-col gap-4 rounded-xl border border-amber-500/40 bg-amber-50 p-5 text-amber-900 sm:flex-row sm:items-start dark:bg-amber-950/40 dark:text-amber-200"
                    >
                        <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white">
                            <MapPinOff className="size-5" />
                        </span>

                        <div className="grid min-w-0 flex-1 gap-3">
                            <div>
                                <h2 className="font-semibold">Pilih ulang lokasi uji</h2>
                                <p className="mt-1 text-sm opacity-90">
                                    {lokasiTersedia.length > 0
                                        ? 'Lokasi uji yang Anda pilih sebelumnya tidak lagi tersedia untuk skema ini. Pilih lokasi baru di bawah.'
                                        : 'Lokasi uji untuk skema ini belum tersedia. Silakan tunggu informasi dari admin LSP.'}
                                </p>
                            </div>

                            {lokasiTersedia.length > 0 && (
                                <>
                                    <div className="text-foreground">
                                        <PilihLokasi
                                            lokasi={lokasiTersedia}
                                            value={lokasiForm.data.id_tuk}
                                            onChange={(value) => lokasiForm.setData('id_tuk', value)}
                                        />
                                    </div>
                                    <InputError message={lokasiForm.errors.id_tuk} />
                                    <Button type="submit" className="justify-self-start" disabled={lokasiForm.processing || !lokasiForm.data.id_tuk}>
                                        Simpan lokasi
                                    </Button>
                                </>
                            )}
                        </div>
                    </form>
                )}

                <div className={cn('flex flex-col gap-4 rounded-xl border p-5 sm:flex-row sm:items-start', hasil.className)}>
                    <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-full text-white', hasil.iconClass)}>
                        <hasil.icon className="size-5" />
                    </span>

                    <div className="min-w-0 flex-1">
                        <h2 className="font-semibold">{hasil.judul}</h2>
                        <p className="mt-1 text-sm opacity-90">{hasil.pesan}</p>

                        {permohonan.catatan_verifikasi && (
                            <div className="mt-3 rounded-lg bg-white/60 p-3 text-sm dark:bg-black/20">
                                <p className="text-xs font-semibold tracking-wide uppercase opacity-70">Catatan verifikator</p>
                                <p className="mt-1 whitespace-pre-line">{permohonan.catatan_verifikasi}</p>
                            </div>
                        )}

                        {permohonan.diverifikasi_pada && <p className="mt-2 text-xs opacity-70">Diverifikasi {permohonan.diverifikasi_pada}</p>}
                    </div>

                    {bisaPerbaiki && (
                        <Button asChild className="shrink-0">
                            <Link href={route('sertifikasi.perbaiki', permohonan.ulid)}>
                                <PencilLine />
                                Perbaiki &amp; ajukan ulang
                            </Link>
                        </Button>
                    )}

                    {bisaBatal && (
                        <Button variant="outline" className="shrink-0 bg-white/70 dark:bg-transparent" onClick={() => setKonfirmasiBatal(true)}>
                            <Ban />
                            Batalkan permohonan
                        </Button>
                    )}
                </div>

                <div className="grid gap-6 xl:grid-cols-2">
                    <DataPemohon pemohon={permohonan.pemohon} />

                    <div className="grid content-start gap-6">
                        <DataSertifikasi permohonan={permohonan} />

                        <Panel title="Bagian 3 · Bukti Kelengkapan">
                            <DokumenList dokumen={permohonan.dokumen} cek={permohonan.status === 'diajukan' ? undefined : permohonan.cek_dokumen} />
                        </Panel>

                        <RiwayatPermohonan riwayat={permohonan.riwayat_status} />
                    </div>
                </div>
            </div>

            <Dialog open={konfirmasiBatal} onOpenChange={(open) => !membatalkan && setKonfirmasiBatal(open)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Batalkan permohonan {permohonan.nomor}?</DialogTitle>
                        <DialogDescription>
                            Permohonan skema <span className="text-foreground font-medium">{permohonan.skema.judul}</span> tidak akan diperiksa admin.
                            Setelah dibatalkan, Anda bisa mendaftar skema lain selama periode masih dibuka.
                        </DialogDescription>
                    </DialogHeader>

                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setKonfirmasiBatal(false)} disabled={membatalkan}>
                            Tidak jadi
                        </Button>
                        <Button variant="destructive" onClick={batalkan} disabled={membatalkan}>
                            {membatalkan && <LoaderCircle className="size-4 animate-spin" />}
                            Ya, batalkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
