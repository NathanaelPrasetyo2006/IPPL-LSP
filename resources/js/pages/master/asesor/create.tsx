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
        title: 'Buat Akun',
        href: '/master/asesor/create',
    },
];

export default function CreateAsesor() {
    const { data, setData, post, errors, processing } = useForm({
        nama: '',
        no_reg: '',
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('master.asesor.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Akun Asesor" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Buat Akun Asesor</h1>
                    <p className="text-muted-foreground mt-1 text-sm">Akun dasar berisi identitas asesor dan email untuk login ke sistem.</p>
                </div>

                <AsesorForm
                    data={data}
                    setData={setData}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Buat akun & kirim email"
                    notice={
                        <>
                            <p className="font-medium">Bagaimana akun ini diaktifkan?</p>
                            <ol className="mt-1 list-decimal space-y-0.5 pl-4">
                                <li>Sistem mengirim satu email berisi email login, password sementara, dan link verifikasi.</li>
                                <li>Setelah link diklik, akun aktif dan asesor bisa langsung login.</li>
                                <li>Asesor wajib mengganti password sementara saat login pertama.</li>
                            </ol>
                        </>
                    }
                />
            </div>
        </AppLayout>
    );
}
