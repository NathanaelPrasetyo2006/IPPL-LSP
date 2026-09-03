import AsesorForm from '@/components/master/asesor-form';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Master Data',
        href: '/master/asesor',
    },
    {
        title: 'Akun Asesor',
        href: '/master/asesor',
    },
    {
        title: 'Ubah',
        href: '#',
    },
];

interface EditAsesorProps {
    asesor: {
        id_asesor: number;
        nama: string;
        no_reg: string;
        email: string | null;
    };
}

export default function EditAsesor({ asesor }: EditAsesorProps) {
    const { data, setData, put, errors, processing } = useForm({
        nama: asesor.nama,
        no_reg: asesor.no_reg,
        email: asesor.email ?? '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('master.asesor.update', asesor.id_asesor));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ubah ${asesor.nama}`} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Ubah Akun Asesor</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Perbarui identitas <span className="font-medium">{asesor.nama}</span> atau setel ulang password sementaranya.
                    </p>
                </div>

                <AsesorForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Simpan perubahan"
                    passwordOptional
                />
            </div>
        </AppLayout>
    );
}
