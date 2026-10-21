import { Head, useForm } from '@inertiajs/react';
import { Info, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import PasswordInput from '@/components/auth/password-input';
import Requirement from '@/components/auth/password-requirement';
import RoleBadge from '@/components/auth/role-badge';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type RegisterForm = {
    nama_lengkap: string;
    no_telp: string;
    email: string;
    password: string;
    password_confirmation: string;
    persetujuan: boolean;
};

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm<RegisterForm>({
        nama_lengkap: '',
        no_telp: '',
        email: '',
        password: '',
        password_confirmation: '',
        persetujuan: false,
    });

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
            title="Registrasi Calon Asesi"
            description="Daftarkan diri Anda untuk mengikuti uji kompetensi di LSP."
            header={<RoleBadge role="asesi" caption="Registrasi" />}
        >
            <Head title="Registrasi Asesi" />

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="nama_lengkap" required>
                        Nama lengkap
                    </Label>

                    <Input
                        id="nama_lengkap"
                        required
                        autoFocus
                        autoComplete="name"
                        value={data.nama_lengkap}
                        onChange={(e) => setData('nama_lengkap', e.target.value)}
                        disabled={processing}
                        placeholder="Sesuai KTP"
                    />

                    <p className="text-muted-foreground text-xs">
                        Nama ini dipakai untuk kode & folder dokumen asesi, serta dicetak pada sertifikat.
                    </p>

                    <InputError message={errors.nama_lengkap} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="no_telp" required>
                        No. HP / WhatsApp
                    </Label>

                    <Input
                        id="no_telp"
                        type="tel"
                        required
                        autoComplete="tel"
                        value={data.no_telp}
                        onChange={(e) => setData('no_telp', e.target.value)}
                        disabled={processing}
                        placeholder="Contoh: 081234567890"
                    />

                    <InputError message={errors.no_telp} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email" required>
                        Email
                    </Label>

                    <Input
                        id="email"
                        type="email"
                        required
                        autoComplete="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value.toLowerCase())}
                        disabled={processing}
                        placeholder="nama@email.com"
                    />

                    <p className="text-muted-foreground text-xs">
                        Gunakan email aktif. Kode verifikasi, hasil verifikasi berkas, dan jadwal asesmen dikirim ke email ini.
                    </p>

                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password" required>
                        Password
                    </Label>

                    <PasswordInput
                        id="password"
                        required
                        autoComplete="new-password"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        disabled={processing}
                        placeholder="Minimal 8 karakter"
                    />

                    <InputError message={errors.password} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation" required>
                        Ulangi password
                    </Label>

                    <PasswordInput
                        id="password_confirmation"
                        required
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

                <div className="grid gap-2">
                    <div className="flex items-start gap-3">
                        <Checkbox
                            id="persetujuan"
                            checked={data.persetujuan}
                            onCheckedChange={(checked) => setData('persetujuan', checked === true)}
                            disabled={processing}
                            className="mt-0.5"
                        />
                        <Label htmlFor="persetujuan" required className="text-sm leading-snug font-normal">
                            Saya menyetujui data pribadi saya digunakan oleh LSP untuk keperluan proses sertifikasi kompetensi.
                        </Label>
                    </div>

                    <InputError message={errors.persetujuan} />
                </div>

                <div className="flex items-start gap-3 rounded-lg border border-sky-500/30 bg-sky-50 p-3 dark:bg-sky-950/40">
                    <Info className="mt-0.5 size-4 shrink-0 text-sky-600 dark:text-sky-400" />
                    <p className="text-xs text-sky-800 dark:text-sky-300">
                        Setelah akun dibuat, verifikasi email Anda lalu isi formulir permohonan sertifikasi (APL-01) beserta dokumen persyaratan.
                    </p>
                </div>

                <Button type="submit" className="w-full" disabled={processing || !longEnough || !matches || !data.persetujuan}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Daftar sebagai calon asesi
                </Button>
            </form>

            <p className="text-muted-foreground mt-6 border-t pt-4 text-center text-sm">
                Sudah punya akun?{' '}
                <TextLink href={route('login.role', 'asesi')} className="font-medium">
                    Masuk di sini
                </TextLink>
            </p>
        </AuthLayout>
    );
}
