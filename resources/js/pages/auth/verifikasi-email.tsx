import { Head } from '@inertiajs/react';

import KodeOtpForm from '@/components/auth/kode-otp-form';
import RoleBadge from '@/components/auth/role-badge';
import TextLink from '@/components/text-link';
import AuthLayout from '@/layouts/auth-layout';
import { type OtpStatus } from '@/types';

interface VerifikasiEmailProps {
    /** Email tujuan dalam bentuk tersamar, mis. bu*******so@gmail.com. */
    email: string;
    otp: OtpStatus;
}

export default function VerifikasiEmail({ email, otp }: VerifikasiEmailProps) {
    return (
        <AuthLayout
            title="Verifikasi Email"
            description={`Masukkan kode 6 digit yang dikirim ke ${email}. Periksa juga folder spam. Akun bisa dipakai setelah email terverifikasi.`}
            header={<RoleBadge role="asesi" caption="Verifikasi email" />}
        >
            <Head title="Verifikasi Email" />

            <KodeOtpForm
                verifikasiUrl={route('verifikasi-email.verify')}
                kirimUlangUrl={route('verifikasi-email.resend')}
                otp={otp}
                submitLabel="Verifikasi & masuk"
            />

            <div className="mt-6 border-t pt-4 text-center text-sm">
                <TextLink href={route('login.role', 'asesi')} className="text-muted-foreground">
                    Kembali ke halaman masuk
                </TextLink>
            </div>
        </AuthLayout>
    );
}
