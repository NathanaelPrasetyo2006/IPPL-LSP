import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type TukOptions } from '@/types';
import { Link } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export interface TukFormData {
    kode: string;
    nama: string;
    jenis: string;
    alamat: string;
    kota: string;
    penanggung_jawab: string;
    telepon: string;
    status: string;
}

interface TukFormProps {
    data: TukFormData;
    setData: (key: keyof TukFormData, value: string) => void;
    errors: Partial<Record<keyof TukFormData, string>>;
    processing: boolean;
    options: TukOptions;
    onSubmit: FormEventHandler;
    submitLabel: string;
}

export default function TukForm({ data, setData, errors, processing, options, onSubmit, submitLabel }: TukFormProps) {
    return (
        <form onSubmit={onSubmit} className="bg-card max-w-3xl rounded-xl border shadow-xs">
            <div className="space-y-6 p-6">
                <div className="grid gap-6 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="kode">Kode TUK</Label>

                        <Input
                            id="kode"
                            value={data.kode}
                            onChange={(e) => setData('kode', e.target.value)}
                            required
                            autoFocus
                            autoComplete="off"
                            placeholder="Contoh: TUK-001"
                        />

                        <p className="text-muted-foreground text-xs">Kode harus unik antar lokasi TUK.</p>

                        <InputError message={errors.kode} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="jenis">Jenis TUK</Label>

                        <Select value={data.jenis} onValueChange={(value) => setData('jenis', value)}>
                            <SelectTrigger id="jenis">
                                <SelectValue placeholder="Pilih jenis TUK" />
                            </SelectTrigger>

                            <SelectContent>
                                {Object.entries(options.jenis).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={errors.jenis} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="nama">Nama TUK</Label>

                    <Input
                        id="nama"
                        value={data.nama}
                        onChange={(e) => setData('nama', e.target.value)}
                        required
                        autoComplete="off"
                        placeholder="Contoh: TUK Mandiri Jakarta"
                    />

                    <InputError message={errors.nama} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="alamat">Alamat</Label>

                    <Input
                        id="alamat"
                        value={data.alamat}
                        onChange={(e) => setData('alamat', e.target.value)}
                        required
                        autoComplete="off"
                        placeholder="Nama jalan, nomor, kelurahan"
                    />

                    <InputError message={errors.alamat} />
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="kota">Kota</Label>

                        <Input
                            id="kota"
                            value={data.kota}
                            onChange={(e) => setData('kota', e.target.value)}
                            required
                            autoComplete="off"
                            placeholder="Contoh: Jakarta"
                        />

                        <InputError message={errors.kota} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="status">Status</Label>

                        <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Pilih status" />
                            </SelectTrigger>

                            <SelectContent>
                                {Object.entries(options.status).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <InputError message={errors.status} />
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="grid gap-2">
                        <Label htmlFor="penanggung_jawab">
                            Penanggung jawab <span className="text-muted-foreground font-normal">(opsional)</span>
                        </Label>

                        <Input
                            id="penanggung_jawab"
                            value={data.penanggung_jawab}
                            onChange={(e) => setData('penanggung_jawab', e.target.value)}
                            autoComplete="off"
                            placeholder="Nama penanggung jawab"
                        />

                        <InputError message={errors.penanggung_jawab} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="telepon">
                            Telepon <span className="text-muted-foreground font-normal">(opsional)</span>
                        </Label>

                        <Input
                            id="telepon"
                            value={data.telepon}
                            onChange={(e) => setData('telepon', e.target.value)}
                            autoComplete="off"
                            inputMode="tel"
                            placeholder="Contoh: 081234567890"
                        />

                        <InputError message={errors.telepon} />
                    </div>
                </div>
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
