import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Requirement from '@/components/auth/password-requirement';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token: string;
    email: string;
}

interface ResetPasswordForm {
    token: string;
    email: string;
    password: string;
    password_confirmation: string;
    [key: string]: string;
}

export default function ResetPassword({ token, email }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token: token,
        email: email,
        password: '',
        password_confirmation: '',
    });

    const [visible, setVisible] = useState(false);

    const longEnough = data.password.length >= 8;
    const matches = data.password !== '' && data.password === data.password_confirmation;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout className="max-w-md" title="Buat Password Baru" description="Masukkan password baru untuk akun Anda.">
            <Head title="Reset Password" />

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>
                    <Input id="email" type="email" name="email" autoComplete="email" value={data.email} readOnly className="bg-muted/50" />
                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2 border-t pt-5">
                    <Label htmlFor="password">Password baru</Label>

                    <div className="relative">
                        <Input
                            id="password"
                            type={visible ? 'text' : 'password'}
                            name="password"
                            required
                            autoFocus
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Minimal 8 karakter"
                            className="pr-10"
                        />

                        <button
                            type="button"
                            onClick={() => setVisible((current) => !current)}
                            className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1"
                            aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'}
                            tabIndex={-1}
                        >
                            {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                        </button>
                    </div>

                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Ulangi password baru</Label>

                    <Input
                        id="password_confirmation"
                        type={visible ? 'text' : 'password'}
                        name="password_confirmation"
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

            <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-sm">
                Tautan kedaluwarsa?{' '}
                <TextLink href={route('login')} className="font-medium">
                    Kembali ke portal masuk
                </TextLink>
            </p>
        </AuthLayout>
    );
}
