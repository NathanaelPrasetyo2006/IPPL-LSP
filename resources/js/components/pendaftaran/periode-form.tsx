import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { cn } from '@/lib/utils';
import { type SkemaOption, type Tuk } from '@/types';
import { Link } from '@inertiajs/react';
import { Lock, MapPin, Plus, Search, Trash2 } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

export type LokasiFormData = {
    id_tuk: string;
    skema: number[];
};

export type PeriodeFormData = {
    nama: string;
    tanggal_buka: string;
    tanggal_tutup: string;
    kuota: string;
    keterangan: string;
    aktif: boolean;
    skema: number[];
    lokasi: LokasiFormData[];
};

interface PeriodeFormProps {
    data: PeriodeFormData;
    setData: <K extends keyof PeriodeFormData>(key: K, value: PeriodeFormData[K]) => void;
    errors: Partial<Record<string, string>>;
    processing: boolean;
    skemaOptions: SkemaOption[];
    tukOptions: Tuk[];
    /** Skema yang sudah punya pendaftar sehingga tidak boleh dilepas. */
    skemaTerkunci?: number[];
    /** Jumlah pendaftar berjalan per "id_tuk-id_skema". */
    pendaftarLokasi?: Record<string, number>;
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
    tukOptions,
    skemaTerkunci = [],
    pendaftarLokasi = {},
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

        // Skema yang dilepas di atas ikut dilepas dari semua lokasi.
        if (!checked) {
            setData(
                'lokasi',
                data.lokasi.map((lokasi) => ({ ...lokasi, skema: lokasi.skema.filter((item) => item !== id) })),
            );
        }
    };

    const skemaError = errors.skema ?? Object.entries(errors).find(([key]) => key.startsWith('skema.'))?.[1];

    // Urutan skema di lokasi mengikuti urutan daftar skema, bukan urutan klik.
    const skemaDibuka = skemaOptions.filter((skema) => data.skema.includes(skema.id_skema));
    const tukTerpakai = data.lokasi.map((lokasi) => lokasi.id_tuk).filter(Boolean);
    const skemaTanpaLokasi = skemaDibuka.filter((skema) => !data.lokasi.some((lokasi) => lokasi.skema.includes(skema.id_skema)));

    const ubahLokasi = (index: number, perubahan: Partial<LokasiFormData>) => {
        setData(
            'lokasi',
            data.lokasi.map((lokasi, i) => (i === index ? { ...lokasi, ...perubahan } : lokasi)),
        );
    };

    const toggleSkemaLokasi = (index: number, id: number, checked: boolean) => {
        const skema = data.lokasi[index].skema;

        ubahLokasi(index, { skema: checked ? [...skema, id] : skema.filter((item) => item !== id) });
    };

    const lokasiError = (index: number, field: 'id_tuk' | 'skema') =>
        errors[`lokasi.${index}.${field}`] ??
        (field === 'skema' ? Object.entries(errors).find(([key]) => key.startsWith(`lokasi.${index}.skema.`))?.[1] : undefined);

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

                <div className="grid gap-3">
                    <div>
                        <Label>Lokasi TUK</Label>
                        <p className="text-muted-foreground mt-1 text-xs">
                            Pilih lokasi, lalu centang skema yang diujikan di lokasi tersebut. Setiap lokasi wajib memiliki minimal satu skema, dan
                            setiap skema wajib ada di minimal satu lokasi.
                        </p>
                    </div>

                    {skemaDibuka.length === 0 ? (
                        <div className="text-muted-foreground flex items-center gap-3 rounded-lg border border-dashed p-6 text-sm">
                            <Lock className="size-4 shrink-0" />
                            Pilih skema yang dibuka terlebih dahulu sebelum menentukan lokasi.
                        </div>
                    ) : tukOptions.length === 0 ? (
                        <p className="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                            Belum ada lokasi TUK.{' '}
                            <Link href={route('master.tuk.create')} className="text-foreground underline">
                                Tambah lokasi TUK
                            </Link>{' '}
                            terlebih dahulu.
                        </p>
                    ) : (
                        <>
                            {data.lokasi.map((lokasi, index) => {
                                const tukError = lokasiError(index, 'id_tuk');
                                const skemaLokasiError = lokasiError(index, 'skema');

                                return (
                                    <div key={index} className={cn('rounded-lg border', (tukError || skemaLokasiError) && 'border-destructive')}>
                                        <div className="bg-muted/40 flex items-center gap-3 rounded-t-lg border-b px-4 py-3">
                                            <MapPin className="text-muted-foreground size-4 shrink-0" />
                                            <span className="text-sm font-medium">Lokasi {index + 1}</span>

                                            {data.lokasi.length > 1 && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    title="Hapus lokasi"
                                                    className="ml-auto size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                    onClick={() =>
                                                        setData(
                                                            'lokasi',
                                                            data.lokasi.filter((_, i) => i !== index),
                                                        )
                                                    }
                                                >
                                                    <Trash2 />
                                                    <span className="sr-only">Hapus lokasi {index + 1}</span>
                                                </Button>
                                            )}
                                        </div>

                                        <div className="grid gap-4 p-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor={`lokasi-${index}`}>Nama lokasi</Label>
                                                <Select value={lokasi.id_tuk} onValueChange={(value) => ubahLokasi(index, { id_tuk: value })}>
                                                    <SelectTrigger id={`lokasi-${index}`}>
                                                        <SelectValue placeholder="Pilih lokasi TUK" />
                                                    </SelectTrigger>

                                                    <SelectContent>
                                                        {tukOptions.map((tuk) => {
                                                            const id = tuk.id_tuk.toString();

                                                            return (
                                                                <SelectItem
                                                                    key={id}
                                                                    value={id}
                                                                    disabled={id !== lokasi.id_tuk && tukTerpakai.includes(id)}
                                                                >
                                                                    {tuk.nama}
                                                                </SelectItem>
                                                            );
                                                        })}
                                                    </SelectContent>
                                                </Select>

                                                {lokasi.id_tuk && (
                                                    <p className="text-muted-foreground text-xs whitespace-pre-line">
                                                        {tukOptions.find((tuk) => tuk.id_tuk.toString() === lokasi.id_tuk)?.alamat}
                                                    </p>
                                                )}

                                                <InputError message={tukError} />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label>Skema di lokasi ini</Label>

                                                {lokasi.id_tuk ? (
                                                    <div className="grid gap-2 sm:grid-cols-2">
                                                        {skemaDibuka.map((skema) => (
                                                            <label
                                                                key={skema.id_skema}
                                                                htmlFor={`lokasi-${index}-skema-${skema.id_skema}`}
                                                                className="hover:bg-muted/50 flex cursor-pointer items-start gap-3 rounded-md border px-3 py-2"
                                                            >
                                                                <Checkbox
                                                                    id={`lokasi-${index}-skema-${skema.id_skema}`}
                                                                    checked={lokasi.skema.includes(skema.id_skema)}
                                                                    onCheckedChange={(value) =>
                                                                        toggleSkemaLokasi(index, skema.id_skema, value === true)
                                                                    }
                                                                    className="mt-0.5"
                                                                />
                                                                <span className="min-w-0 flex-1">
                                                                    <span className="block text-sm">{skema.judul}</span>
                                                                    <span className="text-muted-foreground block text-xs">
                                                                        {skema.nomor}
                                                                        {pendaftarLokasi[`${lokasi.id_tuk}-${skema.id_skema}`] > 0 &&
                                                                            ` · ${pendaftarLokasi[`${lokasi.id_tuk}-${skema.id_skema}`]} pendaftar`}
                                                                    </span>
                                                                </span>
                                                            </label>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <p className="text-muted-foreground text-xs">Pilih lokasi terlebih dahulu.</p>
                                                )}

                                                <InputError message={skemaLokasiError} />
                                            </div>
                                        </div>
                                    </div>
                                );
                            })}

                            <Button
                                type="button"
                                variant="outline"
                                className="justify-self-start"
                                disabled={data.lokasi.length >= tukOptions.length}
                                onClick={() => setData('lokasi', [...data.lokasi, { id_tuk: '', skema: [] }])}
                            >
                                <Plus />
                                Tambah lokasi
                            </Button>

                            {skemaTanpaLokasi.length > 0 && (
                                <p className="text-xs text-amber-700 dark:text-amber-500">
                                    Setiap skema wajib memiliki lokasi. Belum ada lokasi untuk:{' '}
                                    {skemaTanpaLokasi.map((skema) => skema.judul).join(', ')}.
                                </p>
                            )}
                        </>
                    )}

                    <InputError message={errors.lokasi} />
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
