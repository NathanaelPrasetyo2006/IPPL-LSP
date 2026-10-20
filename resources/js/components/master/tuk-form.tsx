import InputError from '@/components/input-error';
import PilihWilayah, { type TingkatWilayah } from '@/components/pendaftaran/pilih-wilayah';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export type TukFormData = Record<'nama' | 'alamat' | TingkatWilayah, string>;

interface TukFormProps {
    data: TukFormData;
    setData: (key: keyof TukFormData, value: string) => void;
    setWilayah: (perubahan: Partial<Record<TingkatWilayah, string>>) => void;
    errors: Partial<Record<keyof TukFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
}

const textareaClass =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-base focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden md:text-sm';

export default function TukForm({ data, setData, setWilayah, errors, processing, onSubmit, submitLabel }: TukFormProps) {
    return (
        <form onSubmit={onSubmit} className="bg-card max-w-3xl rounded-xl border shadow-xs">
            <div className="space-y-6 p-6">
                <div className="grid gap-2">
                    <Label htmlFor="nama">Nama Lokasi</Label>

                    <Input
                        id="nama"
                        value={data.nama}
                        onChange={(e) => setData('nama', e.target.value)}
                        required
                        autoFocus
                        autoComplete="off"
                        placeholder="Contoh: TUK Mandiri Jakarta"
                    />

                    <p className="text-muted-foreground text-xs">Nama harus unik antar lokasi TUK.</p>

                    <InputError message={errors.nama} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="alamat">Detail Alamat</Label>

                    <textarea
                        id="alamat"
                        rows={2}
                        value={data.alamat}
                        onChange={(e) => setData('alamat', e.target.value)}
                        required
                        className={textareaClass}
                        placeholder="Nama jalan, nomor, RT/RW, nama gedung"
                    />

                    <InputError message={errors.alamat} />
                </div>

                <PilihWilayah
                    value={{ provinsi: data.provinsi, kota: data.kota, kecamatan: data.kecamatan, kelurahan: data.kelurahan }}
                    onChange={setWilayah}
                    errors={errors}
                />
            </div>

            <div className="bg-muted/30 flex items-center gap-3 rounded-b-xl border-t px-6 py-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('master.tuk.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
