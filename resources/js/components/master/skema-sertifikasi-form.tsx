import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface SkemaSertifikasiFormData {
    judul: string;
    nomor: string;
}

interface SkemaSertifikasiFormProps {
    data: SkemaSertifikasiFormData;
    setData: (key: keyof SkemaSertifikasiFormData, value: string) => void;
    errors: Partial<Record<keyof SkemaSertifikasiFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
}

export default function SkemaSertifikasiForm({ data, setData, errors, processing, onSubmit, submitLabel }: SkemaSertifikasiFormProps) {
    return (
        <form onSubmit={onSubmit} className="bg-card max-w-2xl rounded-xl border shadow-xs">
            <div className="space-y-6 p-6">
                <div className="grid gap-2">
                    <Label htmlFor="nomor" required>
                        Kode skema
                    </Label>

                    <Input
                        id="nomor"
                        value={data.nomor}
                        // Kode tanpa spasi dan berhuruf besar: spasi dibuang dan huruf diubah kapital saat diketik/ditempel.
                        onChange={(e) => setData('nomor', e.target.value.replace(/\s/g, '').toUpperCase())}
                        required
                        autoFocus
                        autoComplete="off"
                        placeholder="Contoh: SS-KKNI-001"
                    />

                    <p className="text-muted-foreground text-xs">
                        Kode harus unik, tanpa spasi, otomatis huruf besar, dipakai sebagai identitas resmi skema.
                    </p>

                    <InputError message={errors.nomor} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="judul" required>
                        Nama skema sertifikasi
                    </Label>

                    <Input
                        id="judul"
                        value={data.judul}
                        onChange={(e) => setData('judul', e.target.value)}
                        required
                        autoComplete="off"
                        placeholder="Contoh: Junior Web Developer"
                    />

                    <InputError message={errors.judul} />
                </div>
            </div>

            <div className="bg-muted/30 flex items-center gap-3 rounded-b-xl border-t px-6 py-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('master.skema-sertifikasi.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
