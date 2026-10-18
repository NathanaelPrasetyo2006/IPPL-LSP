import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, Info, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Requirement from '@/components/auth/password-requirement';
import RoleBadge from '@/components/auth/role-badge';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface RegisterForm {
    email: string;
    password: string;
    password_confirmation: string;
    [key: string]: string;
}

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        email: '',
        password: '',
        password_confirmation: '',
    });

    const [visible, setVisible] = useState(false);

    const longEnough = data.password.length >= 8;
    const matches = data.password !== '' && data.password === data.password_confirmation;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout
            className="max-w-md"
            title="Buat Akun Asesi"
            description="Daftarkan diri Anda untuk mengikuti uji kompetensi di LSP."
            header={<RoleBadge role="asesi" caption="Registrasi" />}
        >
            <Head title="Registrasi Asesi" />

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>

                    <Input
                        id="email"
                        type="email"
                        required
                        autoFocus
                        tabIndex={1}
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value.toLowerCase())}
                        disabled={processing}
                        placeholder="nama@email.com"
                    />

                    <p className="text-muted-foreground text-xs">Gunakan email aktif. Informasi jadwal & hasil asesmen dikirim ke email ini.</p>

                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password">Password</Label>

                    <div className="relative">
                        <Input
                            id="password"
                            type={visible ? 'text' : 'password'}
                            required
                            tabIndex={2}
                            autoComplete="new-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            disabled={processing}
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
                    <Label htmlFor="password_confirmation">Ulangi password</Label>

                    <Input
                        id="password_confirmation"
                        type={visible ? 'text' : 'password'}
                        required
                        tabIndex={3}
                        autoComplete="new-password"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                        disabled={processing}
                        placeholder="Ketik ulang password"
                    />

                    <InputError message={errors.password_confirmation} />
                </div>

                <ul className="bg-muted/50 space-y-1.5 rounded-lg p-3 text-xs">
                    <Requirement met={longEnough} label="Minimal 8 karakter" />
                    <Requirement met={matches} label="Konfirmasi password cocok" />
                </ul>

                <div className="flex items-start gap-3 rounded-lg border border-sky-500/30 bg-sky-50 p-3 dark:bg-sky-950/40">
                    <Info className="mt-0.5 size-4 shrink-0 text-sky-600 dark:text-sky-400" />
                    <p className="text-xs text-sky-800 dark:text-sky-300">
                        Setelah akun dibuat, Anda akan melengkapi data diri dan dokumen persyaratan (APL-01) dari dashboard.
                    </p>
                </div>

                <Button type="submit" className="w-full" tabIndex={4} disabled={processing || !longEnough || !matches}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Buat akun asesi
                </Button>
            </form>

            <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-sm">
                Sudah punya akun?{' '}
                <TextLink href={route('login.role', 'asesi')} className="font-medium" tabIndex={5}>
                    Masuk di sini
                </TextLink>
            </p>
        </AuthLayout>
    );
}
