import { type BreadcrumbItem, type OtpStatus, type SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { MailWarning } from 'lucide-react';
import { FormEventHandler } from 'react';

import KodeOtpForm from '@/components/auth/kode-otp-form';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import NonaktifkanAkun from '@/components/nonaktifkan-akun';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pengaturan profil',
        href: '/settings/profile',
    },
];

interface ProfileProps {
    /** Email baru yang menunggu kode OTP; email akun belum berubah. */
    emailBaru: string | null;
    otp: OtpStatus | null;
    bisaNonaktif: boolean;
    status?: string;
}

export default function Profile({ emailBaru, otp, bisaNonaktif, status }: ProfileProps) {
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing } = useForm({
        email: emailBaru ?? auth.user.email,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        patch(route('profile.update'), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pengaturan profil" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Informasi akun" description="Email yang dipakai untuk masuk dan menerima pemberitahuan" />

                    {emailBaru && otp ? (
                        <div className="space-y-5 rounded-lg border p-4">
                            <div className="flex items-start gap-3">
                                <MailWarning className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                                <div className="text-sm">
                                    <p className="font-medium">Verifikasi email baru</p>
                                    <p className="text-muted-foreground mt-0.5">
                                        Masukkan kode yang dikirim ke <span className="text-foreground font-medium">{emailBaru}</span>. Sampai kode
                                        dimasukkan, Anda tetap masuk dengan <span className="text-foreground font-medium">{auth.user.email}</span>.
                                    </p>
                                </div>
                            </div>

                            <KodeOtpForm
                                verifikasiUrl={route('profile.email.verify')}
                                kirimUlangUrl={route('profile.email.resend')}
                                otp={otp}
                                submitLabel="Verifikasi & ganti email"
                            />

                            <Button
                                variant="outline"
                                className="w-full"
                                onClick={() => router.delete(route('profile.email.cancel'), { preserveScroll: true })}
                            >
                                Batalkan penggantian email
                            </Button>
                        </div>
                    ) : (
                        <form onSubmit={submit} className="space-y-6">
                            {status && (
                                <p className="rounded-lg border border-emerald-500/40 bg-emerald-50 px-3 py-2 text-sm text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                                    {status}
                                </p>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="email">Alamat email</Label>

                                <Input
                                    id="email"
                                    type="email"
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="username"
                                    placeholder="nama@email.com"
                                />

                                <p className="text-muted-foreground text-xs">
                                    Bila diganti, kode verifikasi dikirim ke email baru. Email baru berlaku setelah kode dimasukkan.
                                </p>

                                <InputError className="mt-2" message={errors.email} />
                            </div>

                            <Button disabled={processing || data.email.trim().toLowerCase() === auth.user.email}>Simpan</Button>
                        </form>
                    )}
                </div>

                {bisaNonaktif && <NonaktifkanAkun />}
            </SettingsLayout>
        </AppLayout>
    );
}
