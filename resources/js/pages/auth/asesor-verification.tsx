import RoleBadge from '@/components/auth/role-badge';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/auth-layout';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import { Info, LinkIcon, MailCheck } from 'lucide-react';

interface AsesorVerificationProps {
    status: 'activated' | 'already' | 'invalid';
    email: string | null;
}

const CONTENT = {
    activated: {
        title: 'Akun Asesor Aktif',
        description: 'Email Anda berhasil diverifikasi.',
        icon: MailCheck,
        tone: 'border-emerald-500/40 bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400',
    },
    already: {
        title: 'Akun Sudah Aktif',
        description: 'Link verifikasi ini sudah pernah dipakai.',
        icon: Info,
        tone: 'border-sky-500/30 bg-sky-50 text-sky-700 dark:bg-sky-950/40 dark:text-sky-400',
    },
    invalid: {
        title: 'Link Tidak Berlaku',
        description: 'Link verifikasi sudah kedaluwarsa atau tidak valid.',
        icon: LinkIcon,
        tone: 'border-amber-500/40 bg-amber-50 text-amber-700 dark:bg-amber-950/40 dark:text-amber-400',
    },
};

export default function AsesorVerification({ status, email }: AsesorVerificationProps) {
    const content = CONTENT[status];
    const Icon = content.icon;

    return (
        <AuthLayout title={content.title} description={content.description} header={<RoleBadge role="asesor" caption="Verifikasi akun" />}>
            <Head title={content.title} />

            <div className={cn('flex items-start gap-3 rounded-lg border p-3 text-sm', content.tone)}>
                <Icon className="mt-0.5 size-4 shrink-0" />

                {status === 'activated' && (
                    <p>
                        Akun <span className="font-semibold">{email}</span> sudah aktif. Silakan masuk memakai password sementara yang tercantum di
                        email yang sama, lalu ganti password tersebut.
                    </p>
                )}

                {status === 'already' && (
                    <p>
                        Akun <span className="font-semibold">{email}</span> sudah aktif. Gunakan password sementara dari email verifikasi, atau pakai
                        menu &ldquo;Lupa password&rdquo; bila tidak menemukannya.
                    </p>
                )}

                {status === 'invalid' && (
                    <p>Link verifikasi berlaku 24 jam. Hubungi admin LSP untuk meminta link verifikasi dan password sementara yang baru.</p>
                )}
            </div>

            <Button asChild className="mt-6 w-full">
                <Link href={route('login.role', 'asesor')}>Masuk sebagai Asesor</Link>
            </Button>
        </AuthLayout>
    );
}
