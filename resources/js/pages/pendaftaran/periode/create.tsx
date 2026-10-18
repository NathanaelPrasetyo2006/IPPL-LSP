import PeriodeForm, { type PeriodeFormData } from '@/components/pendaftaran/periode-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SkemaOption } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendaftaran', href: '/pendaftaran/periode' },
    { title: 'Periode Pendaftaran', href: '/pendaftaran/periode' },
    { title: 'Buka periode', href: '/pendaftaran/periode/create' },
];

export default function CreatePeriode({ skemaOptions }: { skemaOptions: SkemaOption[] }) {
    const { data, setData, post, errors, processing } = useForm<PeriodeFormData>({
        nama: '',
        tanggal_buka: '',
        tanggal_tutup: '',
        kuota: '',
        keterangan: '',
        aktif: true,
        skema: [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('pendaftaran.periode.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buka Periode Pendaftaran" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Buka Periode Pendaftaran</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Tentukan rentang tanggal pendaftaran dan skema yang bisa dipilih calon asesi.
                    </p>
                </div>

                <PeriodeForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    skemaOptions={skemaOptions}
                    onSubmit={submit}
                    submitLabel="Simpan periode"
                />
            </div>
        </AppLayout>
    );
}
