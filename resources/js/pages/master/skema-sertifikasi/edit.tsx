import SkemaSertifikasiForm from '@/components/master/skema-sertifikasi-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SkemaSertifikasi } from '@/types';
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
        title: 'Ubah',
        href: '#',
    },
];

export default function EditSkemaSertifikasi({ skema }: { skema: SkemaSertifikasi }) {
    const { data, setData, put, errors, processing } = useForm({
        judul: skema.judul,
        nomor: skema.nomor,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('master.skema-sertifikasi.update', skema.ulid));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${skema.nomor}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Ubah Skema Sertifikasi</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Perbarui kode atau nama skema <span className="font-medium">{skema.nomor}</span>.
                    </p>
                </div>

                <SkemaSertifikasiForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </AppLayout>
    );
}
