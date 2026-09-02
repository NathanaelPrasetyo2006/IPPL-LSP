import TukForm from '@/components/master/tuk-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Tuk, type TukOptions } from '@/types';
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
        title: 'Ubah',
        href: '#',
    },
];

export default function EditTuk({ tuk, options }: { tuk: Tuk; options: TukOptions }) {
    const { data, setData, put, errors, processing } = useForm({
        kode: tuk.kode,
        nama: tuk.nama,
        jenis: tuk.jenis,
        alamat: tuk.alamat,
        kota: tuk.kota,
        penanggung_jawab: tuk.penanggung_jawab ?? '',
        telepon: tuk.telepon ?? '',
        status: tuk.status,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('master.tuk.update', tuk.id_tuk));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${tuk.nama}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Ubah Lokasi TUK</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Perbarui data lokasi <span className="font-medium">{tuk.nama}</span>.
                    </p>
                </div>

                <TukForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    options={options}
                    onSubmit={submit}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </AppLayout>
    );
}
