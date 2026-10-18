import { Head, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import RoleBadge from '@/components/auth/role-badge';
import { ROLES } from '@/components/auth/roles';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { type UserRole } from '@/types';

interface LoginForm {
    email: string;
    password: string;
    remember: boolean;
    [key: string]: string | boolean;
}

interface LoginProps {
    role: UserRole;
    status?: string;
}

function LoginFooter({ role }: { role: UserRole }) {
    if (role === 'asesi') {
        return (
            <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-sm">
                Belum punya akun?{' '}
                <TextLink href={route('register')} className="font-medium">
                    Daftar sebagai asesi
                </TextLink>
            </p>
        );
    }

    return (
        <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-xs">
            {role === 'asesor'
                ? 'Akun asesor dibuat oleh admin LSP. Hubungi admin bila Anda belum menerima email dan password sementara.'
                : 'Akun admin hanya dibuat oleh pengelola sistem LSP.'}
        </p>
    );
}

export default function Login({ role, status }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        email: '',
        password: '',
        remember: false,
    });

    const [visible, setVisible] = useState(false);

    const meta = ROLES[role];

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login.store', role), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title={`Masuk sebagai ${meta.label}`} description={meta.loginDescription} header={<RoleBadge role={role} />}>
            <Head title={`Masuk ${meta.label}`} />

            {status && (
                <div className="mb-4 rounded-lg border border-emerald-500/40 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                    {status}
                </div>
            )}

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
                        onChange={(e) => setData('email', e.target.value)}
                        placeholder="nama@email.com"
                    />

                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center">
                        <Label htmlFor="password">Password</Label>

                        <TextLink href={route('password.request', role)} className="ml-auto text-sm" tabIndex={5}>
                            Lupa password?
                        </TextLink>
                    </div>

                    <div className="relative">
                        <Input
                            id="password"
                            type={visible ? 'text' : 'password'}
                            required
                            tabIndex={2}
                            autoComplete="current-password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Password"
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

                <div className="flex items-center space-x-3">
                    <Checkbox
                        id="remember"
                        name="remember"
                        tabIndex={3}
                        checked={data.remember}
                        onCheckedChange={(checked) => setData('remember', checked === true)}
                    />
                    <Label htmlFor="remember" className="font-normal">
                        Ingat saya di perangkat ini
                    </Label>
                </div>

                <Button type="submit" className="w-full" tabIndex={4} disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Masuk
                </Button>
            </form>

            <LoginFooter role={role} />
        </AuthLayout>
    );
}
