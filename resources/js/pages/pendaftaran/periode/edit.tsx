import PeriodeForm, { type LokasiFormData, type PeriodeFormData } from '@/components/pendaftaran/periode-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SkemaOption, type Tuk } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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

export default function EditPeriode({ periode, skemaTerpakai, pendaftarLokasi, skemaOptions, tukOptions }: EditPeriodeProps) {
    const { data, setData, put, errors, processing } = useForm<PeriodeFormData>({
        nama: periode.nama,
        tanggal_buka: periode.tanggal_buka,
        tanggal_tutup: periode.tanggal_tutup,
        kuota: periode.kuota?.toString() ?? '',
        keterangan: periode.keterangan ?? '',
        aktif: periode.aktif,
        skema: periode.skema,
        // Periode lama yang belum punya lokasi langsung diberi satu baris kosong.
        lokasi: periode.lokasi.length > 0 ? periode.lokasi : [{ id_tuk: '', skema: [] }],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('pendaftaran.periode.update', periode.id_periode));
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
                />
            </div>
        </AppLayout>
    );
}
