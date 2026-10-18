import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { type SkemaOption } from '@/types';
import { Link } from '@inertiajs/react';
import { Lock, Search } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

export type PeriodeFormData = {
    nama: string;
    tanggal_buka: string;
    tanggal_tutup: string;
    kuota: string;
    keterangan: string;
    aktif: boolean;
    skema: number[];
};

interface PeriodeFormProps {
    data: PeriodeFormData;
    setData: <K extends keyof PeriodeFormData>(key: K, value: PeriodeFormData[K]) => void;
    errors: Partial<Record<string, string>>;
    processing: boolean;
    skemaOptions: SkemaOption[];
    /** Skema yang sudah punya pendaftar sehingga tidak boleh dilepas. */
    skemaTerkunci?: number[];
    onSubmit: FormEventHandler;
    submitLabel: string;
}

const textareaClass =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-base focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden md:text-sm';

export default function PeriodeForm({
    data,
    setData,
    errors,
    processing,
    skemaOptions,
    skemaTerkunci = [],
    onSubmit,
    submitLabel,
}: PeriodeFormProps) {
    const [cari, setCari] = useState('');

    const skemaTampil = useMemo(() => {
        const keyword = cari.trim().toLowerCase();

        return keyword ? skemaOptions.filter((skema) => `${skema.judul} ${skema.nomor}`.toLowerCase().includes(keyword)) : skemaOptions;
    }, [cari, skemaOptions]);

    const toggleSkema = (id: number, checked: boolean) => {
        setData('skema', checked ? [...data.skema, id] : data.skema.filter((item) => item !== id));
    };

    const skemaError = errors.skema ?? Object.entries(errors).find(([key]) => key.startsWith('skema.'))?.[1];

    return (
        <form onSubmit={onSubmit} className="bg-card max-w-3xl rounded-xl border shadow-xs">
            <div className="space-y-6 p-6">
                <div className="grid gap-2">
                    <Label htmlFor="nama">Nama periode</Label>
                    <Input
                        id="nama"
                        value={data.nama}
                        onChange={(e) => setData('nama', e.target.value)}
                        required
                        autoFocus
                        autoComplete="off"
                        placeholder="Contoh: Gelombang 1 Tahun 2026"
                    />
                    <InputError message={errors.nama} />
                </div>

                <div className="grid gap-6 md:grid-cols-3">
                    <div className="grid gap-2">
                        <Label htmlFor="tanggal_buka">Tanggal buka</Label>
                        <DateInput id="tanggal_buka" value={data.tanggal_buka} onChange={(e) => setData('tanggal_buka', e.target.value)} required />
                        <InputError message={errors.tanggal_buka} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="tanggal_tutup">Tanggal tutup</Label>
                        <DateInput
                            id="tanggal_tutup"
                            min={data.tanggal_buka || undefined}
                            value={data.tanggal_tutup}
                            onChange={(e) => setData('tanggal_tutup', e.target.value)}
                            required
                        />
                        <InputError message={errors.tanggal_tutup} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="kuota">Kuota pendaftar</Label>
                        <Input
                            id="kuota"
                            type="number"
                            min={1}
                            inputMode="numeric"
                            value={data.kuota}
                            onChange={(e) => setData('kuota', e.target.value)}
                            placeholder="Tanpa batas"
                        />
                        <InputError message={errors.kuota} />
                    </div>
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="keterangan">Keterangan</Label>
                    <textarea
                        id="keterangan"
                        rows={3}
                        value={data.keterangan}
                        onChange={(e) => setData('keterangan', e.target.value)}
                        className={textareaClass}
                        placeholder="Opsional, mis. informasi biaya atau jadwal asesmen. Ditampilkan ke calon asesi."
                    />
                    <InputError message={errors.keterangan} />
                </div>

                <div className="grid gap-3">
                    <div className="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <Label>Skema yang dibuka</Label>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {data.skema.length} skema dipilih. Calon asesi hanya bisa mendaftar skema ini.
                            </p>
                        </div>

                        <div className="relative sm:w-64">
                            <Search className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2" />
                            <Input
                                value={cari}
                                onChange={(e) => setCari(e.target.value)}
                                placeholder="Cari skema..."
                                className="h-9 pl-9"
                                aria-label="Cari skema"
                            />
                        </div>
                    </div>

                    <div className={cn('max-h-80 divide-y overflow-y-auto rounded-lg border', skemaError && 'border-destructive')}>
                        {skemaTampil.length === 0 && (
                            <p className="text-muted-foreground p-6 text-center text-sm">
                                {skemaOptions.length === 0 ? (
                                    <>
                                        Belum ada skema sertifikasi.{' '}
                                        <Link href={route('master.skema-sertifikasi.create')} className="text-foreground underline">
                                            Tambah skema
                                        </Link>{' '}
                                        terlebih dahulu.
                                    </>
                                ) : (
                                    'Tidak ada skema yang cocok.'
                                )}
                            </p>
                        )}

                        {skemaTampil.map((skema) => {
                            const terkunci = skemaTerkunci.includes(skema.id_skema);
                            const checked = data.skema.includes(skema.id_skema);

                            return (
                                <label
                                    key={skema.id_skema}
                                    htmlFor={`skema-${skema.id_skema}`}
                                    className={cn(
                                        'hover:bg-muted/50 flex cursor-pointer items-start gap-3 px-4 py-3',
                                        terkunci && 'cursor-not-allowed',
                                    )}
                                >
                                    <Checkbox
                                        id={`skema-${skema.id_skema}`}
                                        checked={checked}
                                        disabled={terkunci}
                                        onCheckedChange={(value) => toggleSkema(skema.id_skema, value === true)}
                                        className="mt-0.5"
                                    />

                                    <span className="min-w-0 flex-1">
                                        <span className="block text-sm font-medium">{skema.judul}</span>
                                        <span className="text-muted-foreground block text-xs">
                                            {skema.nomor} · {skema.unit_kompetensi_count ?? 0} unit kompetensi
                                        </span>
                                    </span>

                                    {terkunci && (
                                        <span
                                            className="text-muted-foreground inline-flex items-center gap-1 text-xs"
                                            title="Sudah ada pendaftar, tidak bisa dilepas"
                                        >
                                            <Lock className="size-3" />
                                            Ada pendaftar
                                        </span>
                                    )}
                                </label>
                            );
                        })}
                    </div>

                    <InputError message={skemaError} />
                </div>

                <div className="flex items-start gap-3 rounded-lg border p-4">
                    <Checkbox id="aktif" checked={data.aktif} onCheckedChange={(value) => setData('aktif', value === true)} className="mt-0.5" />
                    <div className="grid gap-1">
                        <Label htmlFor="aktif" className="cursor-pointer">
                            Aktif
                        </Label>
                        <p className="text-muted-foreground text-xs">
                            Periode aktif menerima pendaftaran selama berada di antara tanggal buka dan tutup. Hilangkan centang untuk menutup lebih
                            awal.
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-muted/30 flex items-center gap-3 rounded-b-xl border-t px-6 py-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>

                <Button type="button" variant="outline" asChild>
                    <Link href={route('pendaftaran.periode.index')}>Batal</Link>
                </Button>
            </div>
        </form>
    );
}
