import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import { MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

export interface AsesorFormData {
    nama: string;
    no_reg: string;
    email: string;
}

interface AsesorFormProps {
    data: AsesorFormData;
    setData: (key: keyof AsesorFormData, value: string) => void;
    errors: Partial<Record<keyof AsesorFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
    /** Keterangan alur aktivasi di bawah form. */
    notice?: React.ReactNode;
}

export default function AsesorForm({ data, setData, errors, processing, onSubmit, submitLabel, notice }: AsesorFormProps) {
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

                    <p className="text-muted-foreground text-xs">
                        Dipakai asesor untuk login. Pastikan email aktif karena link verifikasi dikirim ke sini.
                    </p>

                    <InputError message={errors.email} />
                </div>

                {notice && (
                    <div className="flex items-start gap-3 rounded-lg border border-sky-500/30 bg-sky-50 p-3 dark:bg-sky-950/40">
                        <MailCheck className="mt-0.5 size-4 shrink-0 text-sky-600 dark:text-sky-400" />
                        <div className="text-sm text-sky-800 dark:text-sky-300">{notice}</div>
                    </div>
                )}
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
