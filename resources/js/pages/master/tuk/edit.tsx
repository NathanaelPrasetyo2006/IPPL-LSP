import TukForm, { type TukFormData } from '@/components/master/tuk-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
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

/** Lokasi beserta kolom wilayahnya; TUK lama yang belum punya wilayah bernilai null. */
type TukEdit = { ulid: string } & { [K in keyof TukFormData]: string | null };

export default function EditTuk({ tuk }: { tuk: TukEdit }) {
    const { data, setData, put, errors, processing } = useForm<TukFormData>({
        nama: tuk.nama ?? '',
        alamat: tuk.alamat ?? '',
        provinsi: tuk.provinsi ?? '',
        kota: tuk.kota ?? '',
        kecamatan: tuk.kecamatan ?? '',
        kelurahan: tuk.kelurahan ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('master.tuk.update', tuk.ulid));
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
                    setWilayah={(perubahan) => setData((current) => ({ ...current, ...perubahan }))}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Simpan perubahan"
                />
            </div>
        </AppLayout>
    );
}
