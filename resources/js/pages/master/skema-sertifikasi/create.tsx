import SkemaSertifikasiForm from '@/components/master/skema-sertifikasi-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/skema-sertifikasi',
    },
    {
        title: 'Skema Sertifikasi',
        href: '/master/skema-sertifikasi',
    },
    {
        title: 'Tambah',
        href: '/master/skema-sertifikasi/create',
    },
];

export default function CreateSkemaSertifikasi() {
    const { data, setData, post, errors, processing } = useForm({
        judul: '',
        nomor: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('master.skema-sertifikasi.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Skema Sertifikasi" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Tambah Skema Sertifikasi</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Daftarkan skema sertifikasi baru ke dalam master data.</p>
                </div>

                <SkemaSertifikasiForm data={data} setData={setData} errors={errors} processing={processing} onSubmit={submit} submitLabel="Simpan" />
            </div>
        </AppLayout>
    );
}
