import PasswordInput from '@/components/auth/password-input';
import Requirement from '@/components/auth/password-requirement';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { KeyRound, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

interface FirstPasswordProps {
    email: string;
    nama: string | null;
}

interface FirstPasswordForm {
    current_password: string;
    password: string;
    password_confirmation: string;
    [key: string]: string;
}

export default function FirstPassword({ email, nama }: FirstPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<FirstPasswordForm>({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const longEnough = data.password.length >= 8;
    const isDifferent = data.password !== '' && data.password !== data.current_password;
    const matches = data.password !== '' && data.password === data.password_confirmation;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.first-change.update'), {
            onError: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            className="max-w-md"
            title="Ganti Password Sementara"
            description="Demi keamanan akun, password sementara wajib diganti. Menu lain baru bisa dibuka setelah password diganti."
        >
            <Head title="Ganti Password Sementara" />

            <div className="mb-6 flex items-start gap-3 rounded-lg border border-amber-500/40 bg-amber-50 p-3 dark:bg-amber-950/40">
                <KeyRound className="mt-0.5 size-4 shrink-0 text-amber-600 dark:text-amber-400" />

                <div className="text-sm">
                    <p className="font-medium text-amber-800 dark:text-amber-300">Login perdana</p>
                    <p className="mt-0.5 text-amber-700 dark:text-amber-400/90">
                        {nama ? `${nama} — ` : ''}
                        {email}
                    </p>
                </div>
            </div>

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="current_password">Password sementara</Label>

                    <PasswordInput
                        id="current_password"
                        required
                        autoFocus
                        autoComplete="current-password"
                        value={data.current_password}
                        onChange={(e) => setData('current_password', e.target.value)}
                        placeholder="Password dari admin LSP"
                    />

                    <InputError message={errors.current_password} />
                </div>

                <div className="grid gap-2 border-t pt-5">
                    <Label htmlFor="password">Password baru</Label>

                    <PasswordInput
                        id="password"
                        required
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
                    <Requirement met={isDifferent} label="Berbeda dari password sementara" />
                    <Requirement met={matches} label="Konfirmasi password cocok" />
                </ul>

                <Button type="submit" className="w-full" disabled={processing || !longEnough || !isDifferent || !matches}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Simpan password baru
                </Button>
            </form>

            <div className="mt-6 border-t pt-4 text-center">
                <p className="text-muted-foreground mb-2 text-xs">Bila keluar sekarang, halaman ini akan muncul lagi saat Anda masuk berikutnya.</p>
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="text-muted-foreground hover:text-foreground text-sm underline-offset-4 hover:underline"
                >
                    Keluar
                </Link>
            </div>
        </AuthLayout>
    );
}
