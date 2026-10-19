import { useForm } from '@inertiajs/react';
import { FormEventHandler, useRef } from 'react';

import PasswordInput from '@/components/auth/password-input';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

/**
 * Asesi menonaktifkan akunnya sendiri. Data tidak dihapus; akun bisa diaktifkan kembali
 * lewat halaman masuk atau registrasi dengan email yang sama.
 */
export default function NonaktifkanAkun() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const { data, setData, delete: destroy, processing, reset, errors, clearErrors } = useForm({ password: '' });

    const nonaktifkan: FormEventHandler = (e) => {
        e.preventDefault();

        destroy(route('profile.destroy'), {
            preserveScroll: true,
            onError: () => passwordInput.current?.focus(),
            onFinish: () => reset(),
        });
    };

    const tutup = () => {
        clearErrors();
        reset();
    };

    return (
        <div className="space-y-6">
            <HeadingSmall title="Nonaktifkan akun" description="Hentikan sementara pemakaian akun asesi Anda" />

            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium">Perhatian</p>
                    <p className="text-sm">
                        Setelah dinonaktifkan Anda akan keluar dan tidak bisa masuk. Data diri dan riwayat permohonan tetap tersimpan. Akun bisa
                        diaktifkan kembali lewat halaman masuk atau registrasi dengan email yang sama, lalu verifikasi kode yang dikirim ke email.
                    </p>
                </div>

                <Dialog>
                    <DialogTrigger asChild>
                        <Button variant="destructive">Nonaktifkan akun</Button>
                    </DialogTrigger>

                    <DialogContent>
                        <DialogTitle>Nonaktifkan akun Anda?</DialogTitle>
                        <DialogDescription>Masukkan password untuk memastikan ini memang Anda.</DialogDescription>

                        <form className="space-y-6" onSubmit={nonaktifkan}>
                            <div className="grid gap-2">
                                <Label htmlFor="password" className="sr-only">
                                    Password
                                </Label>

                                <PasswordInput
                                    id="password"
                                    name="password"
                                    ref={passwordInput}
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Password"
                                    autoComplete="current-password"
                                />

                                <InputError message={errors.password} />
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary" onClick={tutup}>
                                        Batal
                                    </Button>
                                </DialogClose>

                                <Button variant="destructive" disabled={processing} asChild>
                                    <button type="submit">Nonaktifkan</button>
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </div>
    );
}
