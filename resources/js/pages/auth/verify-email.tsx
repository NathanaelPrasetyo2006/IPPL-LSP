import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { type SharedData } from '@/types';

export default function VerifyEmail({ status }: { status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <AuthLayout className="max-w-md" title="Verifikasi email Anda" description="Satu langkah lagi sebelum Anda dapat mendaftar sertifikasi.">
            <Head title="Verifikasi email" />

            <div className="mb-6 flex items-start gap-3 rounded-lg border border-sky-500/30 bg-sky-50 p-4 dark:bg-sky-950/40">
                <MailCheck className="mt-0.5 size-5 shrink-0 text-sky-600 dark:text-sky-400" />
                <p className="text-sm text-sky-800 dark:text-sky-300">
                    Kami telah mengirim link verifikasi ke <span className="font-semibold">{auth.user.email}</span>. Buka email tersebut dan klik
                    tombol <span className="font-semibold">Verifikasi Email</span>. Periksa juga folder Spam bila tidak ada di kotak masuk.
                </p>
            </div>

            {status === 'verification-link-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">Link verifikasi baru sudah dikirim ke email Anda.</div>
            )}

            <form onSubmit={submit} className="space-y-6 text-center">
                <Button disabled={processing} variant="secondary">
                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                    Kirim ulang email verifikasi
                </Button>

                <TextLink href={route('logout')} method="post" className="mx-auto block text-sm">
                    Keluar
                </TextLink>
            </form>
        </AuthLayout>
    );
}
