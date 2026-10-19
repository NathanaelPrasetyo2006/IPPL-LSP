import { Head, router } from '@inertiajs/react';
import { LoaderCircle, UserRoundX } from 'lucide-react';
import { useState } from 'react';

import KodeOtpForm from '@/components/auth/kode-otp-form';
import RoleBadge from '@/components/auth/role-badge';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { type OtpStatus, type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface AktivasiAkunProps {
    email: string;
    asal: 'login' | 'registrasi';
    nonaktifSejak: string;
    otp: OtpStatus;
}

export default function AktivasiAkun({ email, asal, nonaktifSejak, otp }: AktivasiAkunProps) {
    const { errors } = usePage<SharedData>().props;
    const [mengirim, setMengirim] = useState(false);

    const kirimKode = () => {
        router.post(route('aktivasi.kirim'), {}, { onStart: () => setMengirim(true), onFinish: () => setMengirim(false) });
    };

    return (
        <AuthLayout
            title="Aktifkan Kembali Akun"
            description={
                otp.terkirim
                    ? `Masukkan kode 6 digit yang dikirim ke ${email} untuk mengaktifkan kembali akun Anda.`
                    : 'Akun asesi dengan email ini sudah dinonaktifkan.'
            }
            header={<RoleBadge role="asesi" caption="Aktivasi akun" />}
        >
            <Head title="Aktifkan Kembali Akun" />

            {!otp.terkirim ? (
                <div className="flex flex-col gap-5">
                    <div className="flex items-start gap-3 rounded-lg border border-amber-500/40 bg-amber-50 p-4 dark:bg-amber-950/40">
                        <UserRoundX className="mt-0.5 size-5 shrink-0 text-amber-600 dark:text-amber-400" />
                        <div className="text-sm text-amber-900 dark:text-amber-200">
                            <p className="font-medium">
                                {asal === 'registrasi' ? 'Akun dengan email ini sudah ada, tetapi nonaktif.' : 'Akun ini nonaktif.'}
                            </p>
                            <p className="mt-1 opacity-90">
                                Dinonaktifkan sejak {nonaktifSejak}. Aktifkan kembali? Data diri dan riwayat permohonan Anda tetap tersimpan
                                {asal === 'registrasi' && ', dan password akan diganti dengan password yang baru Anda isi'}.
                            </p>
                        </div>
                    </div>

                    <p className="text-muted-foreground text-sm">
                        Kami akan mengirim kode verifikasi ke <span className="text-foreground font-medium">{email}</span>.
                    </p>

                    <InputError message={errors.kode} />

                    <Button className="w-full" onClick={kirimKode} disabled={mengirim}>
                        {mengirim && <LoaderCircle className="size-4 animate-spin" />}
                        Aktifkan kembali &amp; kirim kode
                    </Button>
                </div>
            ) : (
                <KodeOtpForm
                    verifikasiUrl={route('aktivasi.verifikasi')}
                    kirimUlangUrl={route('aktivasi.kirim')}
                    otp={otp}
                    submitLabel="Aktifkan akun"
                />
            )}

            <div className="mt-6 border-t pt-4 text-center text-sm">
                <TextLink href={route('login.role', 'asesi')} className="text-muted-foreground">
                    Batal dan kembali ke halaman masuk
                </TextLink>
            </div>
        </AuthLayout>
    );
}
