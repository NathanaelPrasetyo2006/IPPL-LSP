import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import RoleBadge from '@/components/auth/role-badge';
import { ROLES } from '@/components/auth/roles';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { type UserRole } from '@/types';

interface ForgotPasswordProps {
    role: UserRole;
    status?: string;
}

export default function ForgotPassword({ role, status }: ForgotPasswordProps) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const meta = ROLES[role];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email', role));
    };

    return (
        <AuthLayout
            title="Lupa Password"
            description={`Masukkan email akun ${meta.label} Anda. Kami akan mengirim tautan untuk membuat password baru.`}
            header={<RoleBadge role={role} caption="Lupa password" />}
        >
            <Head title={`Lupa Password ${meta.label}`} />

            {status && (
                <div className="mb-5 flex items-start gap-3 rounded-lg border border-emerald-500/40 bg-emerald-50 p-3 dark:bg-emerald-950/40">
                    <MailCheck className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <p className="text-sm text-emerald-700 dark:text-emerald-400">{status}</p>
                </div>
            )}

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>

                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autoFocus
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="nama@email.com"
                    />

                    <InputError message={errors.email} />
                </div>

                <Button className="w-full" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Kirim tautan reset password
                </Button>
            </form>

            <div className="mt-6 border-t pt-4 text-center text-sm">
                <TextLink href={route('login.role', role)} className="text-muted-foreground inline-flex items-center gap-1.5">
                    <ArrowLeft className="size-3.5" />
                    Kembali ke halaman masuk
                </TextLink>
            </div>
        </AuthLayout>
    );
}
