import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import { Check, Copy, Eye, EyeOff, RefreshCw } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

export interface AsesorFormData {
    nama: string;
    no_reg: string;
    email: string;
    password: string;
}

interface AsesorFormProps {
    data: AsesorFormData;
    setData: (key: keyof AsesorFormData, value: string) => void;
    errors: Partial<Record<keyof AsesorFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
    /** Saat mengubah data, password hanya diganti bila diisi. */
    passwordOptional?: boolean;
}

// Huruf/angka yang mudah dibedakan saat password dibacakan atau diketik ulang asesor.
const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789';

function generatePassword(length = 12): string {
    const values = new Uint32Array(length);
    crypto.getRandomValues(values);

    return Array.from(values, (value) => ALPHABET[value % ALPHABET.length]).join('');
}

export default function AsesorForm({ data, setData, errors, processing, onSubmit, submitLabel, passwordOptional = false }: AsesorFormProps) {
    const [visible, setVisible] = useState(false);
    const [copied, setCopied] = useState(false);

    const copyPassword = async () => {
        if (!data.password) {
            return;
        }

        try {
            await navigator.clipboard.writeText(data.password);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Clipboard bisa ditolak browser (mis. bukan HTTPS); password tetap bisa disalin manual.
            setVisible(true);
        }
    };

    return (
        <form onSubmit={onSubmit} className="bg-card max-w-3xl rounded-xl border shadow-xs">
            <div className="space-y-6 p-6">
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="nama">Nama asesor</Label>

                        <Input
                            id="nama"
                            value={data.nama}
                            onChange={(e) => setData('nama', e.target.value)}
                            required
                            autoFocus
                            autoComplete="off"
                            placeholder="Nama lengkap sesuai sertifikat"
                        />

                        <InputError message={errors.nama} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="no_reg">No. Registrasi / MET</Label>

                        <Input
                            id="no_reg"
                            value={data.no_reg}
                            onChange={(e) => setData('no_reg', e.target.value)}
                            required
                            autoComplete="off"
                            placeholder="Contoh: MET.000.001.2026"
                        />

                        <p className="text-muted-foreground text-xs">Nomor registrasi asesor dari BNSP, harus unik.</p>

                        <InputError message={errors.no_reg} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Email</Label>

                    <Input
                        id="email"
                        type="email"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                        required
                        autoComplete="off"
                        placeholder="asesor@lsp.example.id"
                    />

                    <p className="text-muted-foreground text-xs">Dipakai asesor untuk login ke sistem.</p>

                    <InputError message={errors.email} />
                </div>

                <div className="grid gap-2 border-t pt-6">
                    <Label htmlFor="password">
                        Password sementara
                        {passwordOptional && <span className="text-muted-foreground font-normal"> (kosongkan bila tidak diubah)</span>}
                    </Label>

                    <div className="flex flex-col gap-2 sm:flex-row">
                        <div className="relative flex-1">
                            <Input
                                id="password"
                                type={visible ? 'text' : 'password'}
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required={!passwordOptional}
                                autoComplete="new-password"
                                placeholder="Minimal 8 karakter"
                                className="pr-10"
                            />

                            <button
                                type="button"
                                onClick={() => setVisible((current) => !current)}
                                className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1"
                                aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'}
                            >
                                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                            </button>
                        </div>

                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => {
                                    setData('password', generatePassword());
                                    setVisible(true);
                                }}
                            >
                                <RefreshCw />
                                Generate
                            </Button>

                            <Button type="button" variant="outline" onClick={copyPassword} disabled={!data.password}>
                                {copied ? <Check /> : <Copy />}
                                {copied ? 'Tersalin' : 'Salin'}
                            </Button>
                        </div>
                    </div>

                    <p className="text-muted-foreground text-xs">
                        Catat atau salin password ini sekarang &mdash; setelah disimpan, password tidak bisa dilihat lagi dan hanya bisa diganti
                        dengan yang baru.
                    </p>

                    <InputError message={errors.password} />
                </div>
            </div>

            <div className="bg-muted/30 flex items-center gap-3 rounded-b-xl border-t px-6 py-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('master.asesor.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
