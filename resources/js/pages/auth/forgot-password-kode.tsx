import { Head } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import KodeOtpForm from '@/components/auth/kode-otp-form';
import RoleBadge from '@/components/auth/role-badge';
import { ROLES } from '@/components/auth/roles';
import TextLink from '@/components/text-link';
import AuthLayout from '@/layouts/auth-layout';
import { type OtpStatus, type UserRole } from '@/types';

interface ForgotPasswordKodeProps {
    role: UserRole;
    /** Email tujuan dalam bentuk tersamar, mis. bu*******so@gmail.com. */
    email: string;
    otp: OtpStatus;
}

export default function ForgotPasswordKode({ role, email, otp }: ForgotPasswordKodeProps) {
    const meta = ROLES[role];

    return (
        <AuthLayout
            title="Masukkan Kode Verifikasi"
            description={`Kode 6 digit sudah dikirim ke ${email}. Periksa juga folder spam.`}
            header={<RoleBadge role={role} caption="Lupa password · 2 dari 3" gantiPeran={false} />}
        >
            <Head title={`Kode Verifikasi ${meta.label}`} />

            <KodeOtpForm verifikasiUrl={route('password.otp.verify', role)} kirimUlangUrl={route('password.otp.resend', role)} otp={otp} />

            <div className="mt-6 border-t pt-4 text-center text-sm">
                <TextLink href={route('password.request', role)} className="text-muted-foreground inline-flex items-center gap-1.5">
                    <ArrowLeft className="size-3.5" />
                    Ganti email
                </TextLink>
            </div>
        </AuthLayout>
    );
}
