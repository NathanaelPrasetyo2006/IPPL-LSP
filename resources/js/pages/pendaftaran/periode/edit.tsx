import PeriodeForm, { type LokasiFormData, type PeriodeFormData } from '@/components/pendaftaran/periode-form';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SkemaOption, type Tuk } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, TriangleAlert } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendaftaran', href: '/pendaftaran/periode' },
    { title: 'Periode Pendaftaran', href: '/pendaftaran/periode' },
    { title: 'Ubah', href: '#' },
];

interface EditPeriodeProps {
    periode: {
        id_periode: number;
        nama: string;
        tanggal_buka: string;
        tanggal_tutup: string;
        kuota: number | null;
        keterangan: string | null;
        aktif: boolean;
        skema: number[];
        lokasi: LokasiFormData[];
    };
    skemaTerpakai: number[];
    pendaftarLokasi: Record<string, number>;
    skemaOptions: SkemaOption[];
    tukOptions: Tuk[];
}

const tanggal = (value: string) => (value ? new Date(`${value}T00:00:00`).toLocaleDateString('id-ID', { dateStyle: 'long' }) : '-');

const sama = (a: number[], b: number[]) => a.length === b.length && [...a].sort().every((id, i) => id === [...b].sort()[i]);

/** Lokasi yang sudah dipilih TUK-nya, sebagai peta id_tuk → id skema. */
const petaLokasi = (lokasi: LokasiFormData[]) => new Map(lokasi.filter((item) => item.id_tuk).map((item) => [item.id_tuk, item.skema]));

/**
 * Ringkasan apa saja yang berubah dibanding data awal, untuk ditampilkan di dialog konfirmasi.
 * Urutan klik skema tidak dianggap perubahan.
 */
function daftarPerubahan(awal: PeriodeFormData, kini: PeriodeFormData, skemaOptions: SkemaOption[], tukOptions: Tuk[]): string[] {
    const judulSkema = (id: number) => skemaOptions.find((skema) => skema.id_skema === id)?.judul ?? `Skema #${id}`;
    const namaTuk = (id: string) => tukOptions.find((tuk) => tuk.id_tuk.toString() === id)?.nama ?? `TUK #${id}`;
    const perubahan: string[] = [];

    const teks = (label: string, lama: string, baru: string) => {
        if (lama !== baru) {
            perubahan.push(`${label}: ${lama || '(kosong)'} → ${baru || '(kosong)'}`);
        }
    };

    teks('Nama periode', awal.nama.trim(), kini.nama.trim());
    teks('Tanggal buka', tanggal(awal.tanggal_buka), tanggal(kini.tanggal_buka));
    teks('Tanggal tutup', tanggal(awal.tanggal_tutup), tanggal(kini.tanggal_tutup));
    teks('Kuota', awal.kuota, kini.kuota);
    teks('Keterangan', awal.keterangan.trim(), kini.keterangan.trim());
    teks('Status', awal.aktif ? 'Aktif' : 'Ditutup', kini.aktif ? 'Aktif' : 'Ditutup');

    const ditambah = kini.skema.filter((id) => !awal.skema.includes(id));
    const dilepas = awal.skema.filter((id) => !kini.skema.includes(id));

    if (ditambah.length > 0) {
        perubahan.push(`Skema ditambah: ${ditambah.map(judulSkema).join(', ')}`);
    }

    if (dilepas.length > 0) {
        perubahan.push(`Skema dilepas: ${dilepas.map(judulSkema).join(', ')}`);
    }

    const lokasiAwal = petaLokasi(awal.lokasi);
    const lokasiKini = petaLokasi(kini.lokasi);

    lokasiKini.forEach((skema, idTuk) => {
        const sebelumnya = lokasiAwal.get(idTuk);

        if (!sebelumnya) {
            perubahan.push(`Lokasi ditambah: ${namaTuk(idTuk)} (${skema.map(judulSkema).join(', ') || 'belum ada skema'})`);
        } else if (!sama(sebelumnya, skema)) {
            const masuk = skema.filter((id) => !sebelumnya.includes(id)).map((id) => `+ ${judulSkema(id)}`);
            const keluar = sebelumnya.filter((id) => !skema.includes(id)).map((id) => `− ${judulSkema(id)}`);
            perubahan.push(`Skema di ${namaTuk(idTuk)}: ${[...masuk, ...keluar].join(', ')}`);
        }
    });

    lokasiAwal.forEach((_, idTuk) => {
        if (!lokasiKini.has(idTuk)) {
            perubahan.push(`Lokasi dihapus: ${namaTuk(idTuk)}`);
        }
    });

    return perubahan;
}

export default function EditPeriode({ periode, skemaTerpakai, pendaftarLokasi, skemaOptions, tukOptions }: EditPeriodeProps) {
    const [awal] = useState<PeriodeFormData>(() => ({
        nama: periode.nama,
        tanggal_buka: periode.tanggal_buka,
        tanggal_tutup: periode.tanggal_tutup,
        kuota: periode.kuota?.toString() ?? '',
        keterangan: periode.keterangan ?? '',
        aktif: periode.aktif,
        skema: periode.skema,
        // Periode lama yang belum punya lokasi langsung diberi satu baris kosong.
        lokasi: periode.lokasi.length > 0 ? periode.lokasi : [{ id_tuk: '', skema: [] }],
    }));

    const { data, setData, put, errors, processing } = useForm<PeriodeFormData>(awal);
    const [konfirmasi, setKonfirmasi] = useState(false);

    const perubahan = useMemo(() => daftarPerubahan(awal, data, skemaOptions, tukOptions), [awal, data, skemaOptions, tukOptions]);

    // Pendaftar yang lokasi pilihannya hilang setelah disimpan akan diminta memilih ulang.
    const pendaftarTerdampak = useMemo(() => {
        const tersedia = new Set(data.lokasi.flatMap((lokasi) => lokasi.skema.map((idSkema) => `${lokasi.id_tuk}-${idSkema}`)));

        return Object.entries(pendaftarLokasi).reduce((total, [kunci, jumlah]) => (tersedia.has(kunci) ? total : total + jumlah), 0);
    }, [data.lokasi, pendaftarLokasi]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (perubahan.length > 0) {
            setKonfirmasi(true);
        }
    };

    const simpan = () => {
        put(route('pendaftaran.periode.update', periode.id_periode), {
            onFinish: () => setKonfirmasi(false),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${periode.nama}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Ubah Periode Pendaftaran</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Perbarui jadwal, skema, dan lokasi untuk <span className="font-medium">{periode.nama}</span>.
                    </p>
                </div>

                <PeriodeForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    skemaOptions={skemaOptions}
                    tukOptions={tukOptions}
                    skemaTerkunci={skemaTerpakai}
                    pendaftarLokasi={pendaftarLokasi}
                    onSubmit={submit}
                    submitLabel="Simpan perubahan"
                    submitDisabled={perubahan.length === 0}
                    submitHint={perubahan.length === 0 ? 'Belum ada perubahan dari data awal.' : undefined}
                />
            </div>

            <Dialog open={konfirmasi} onOpenChange={(open) => !processing && setKonfirmasi(open)}>
                <DialogContent className="sm:max-w-lg">
                    <DialogHeader>
                        <DialogTitle>Simpan perubahan periode?</DialogTitle>
                        <DialogDescription>Periksa kembali perubahan berikut sebelum disimpan.</DialogDescription>
                    </DialogHeader>

                    <ul className="max-h-72 list-disc space-y-1.5 overflow-y-auto pl-5 text-sm">
                        {perubahan.map((item) => (
                            <li key={item}>{item}</li>
                        ))}
                    </ul>

                    {pendaftarTerdampak > 0 && (
                        <div className="flex items-start gap-2 rounded-lg border border-amber-500/40 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                            <TriangleAlert className="mt-0.5 size-4 shrink-0" />
                            <p>
                                {pendaftarTerdampak} pendaftar memilih lokasi yang akan dihapus. Mereka akan diberi tahu dan diminta memilih ulang
                                lokasi.
                            </p>
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button variant="outline" onClick={() => setKonfirmasi(false)} disabled={processing}>
                            Periksa lagi
                        </Button>
                        <Button onClick={simpan} disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Ya, simpan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
