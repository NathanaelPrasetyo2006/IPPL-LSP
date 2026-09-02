import TukForm from '@/components/master/tuk-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type TukOptions } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/tuk',
    },
    {
        title: 'Lokasi TUK',
        href: '/master/tuk',
    },
    {
        title: 'Tambah',
        href: '/master/tuk/create',
    },
];

export default function CreateTuk({ options }: { options: TukOptions }) {
    const { data, setData, post, errors, processing } = useForm({
        kode: '',
        nama: '',
        jenis: '',
        alamat: '',
        kota: '',
        penanggung_jawab: '',
        telepon: '',
        status: 'aktif',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('master.tuk.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tambah Lokasi TUK" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Tambah Lokasi TUK</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Daftarkan Tempat Uji Kompetensi baru ke dalam master data.</p>
                </div>

                <TukForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    options={options}
                    onSubmit={submit}
                    submitLabel="Simpan"
                />
            </div>
        </AppLayout>
    );
}
