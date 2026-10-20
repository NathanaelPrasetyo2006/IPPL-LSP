import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import PasswordInput from '@/components/auth/password-input';
import Requirement from '@/components/auth/password-requirement';
import RoleBadge from '@/components/auth/role-badge';
import { ROLES } from '@/components/auth/roles';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { type UserRole } from '@/types';

interface ResetPasswordProps {
    role: UserRole;
    email: string;
}

export default function ResetPassword({ role, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
        password_confirmation: '',
    });

    const meta = ROLES[role];
    const longEnough = data.password.length >= 8;
    const matches = data.password !== '' && data.password === data.password_confirmation;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.store', role), {
            onError: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            title="Buat Password Baru"
            description={`Kode benar. Buat password baru untuk akun ${email}.`}
            header={<RoleBadge role={role} caption="Lupa password · 3 dari 3" gantiPeran={false} />}
        >
            <Head title={`Password Baru ${meta.label}`} />

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="password">Password baru</Label>
                    <PasswordInput
                        id="password"
                        required
                        autoFocus
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        placeholder="Minimal 8 karakter"
                    />
                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Ulangi password baru</Label>
                    <PasswordInput
                        id="password_confirmation"
                        required
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        placeholder="Ketik ulang password baru"
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <ul className="bg-muted/50 space-y-1.5 rounded-lg p-3 text-xs">
                    <Requirement met={longEnough} label="Minimal 8 karakter" />
                    <Requirement met={matches} label="Konfirmasi password cocok" />
                </ul>

                <Button type="submit" className="w-full" disabled={processing || !longEnough || !matches}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Simpan password baru
                </Button>
            </form>
        </AuthLayout>
    );
}
