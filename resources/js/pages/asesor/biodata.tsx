import InputError from '@/components/input-error';
import PilihWilayah from '@/components/pendaftaran/pilih-wilayah';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { useInitials } from '@/hooks/use-initials';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type AsesorBiodata, type AsesorBiodataOptions, type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { CircleCheck, IdCard, ImageUp, Info, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useMemo } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil Asesor',
        href: '/asesor/biodata',
    },
    {
        title: 'Biodata',
        href: '/asesor/biodata',
    },
];

interface BiodataProps {
    asesor: AsesorBiodata;
    options: AsesorBiodataOptions;
}

// Type alias (bukan interface) supaya memenuhi constraint FormDataType milik useForm.
type BiodataForm = {
    _method: 'put';
    nik: string;
    tempat_lahir: string;
    tgl_lahir: string;
    jenis_kelamin: string;
    no_telp: string;
    alamat: string;
    provinsi: string;
    kota: string;
    kecamatan: string;
    kelurahan: string;
    pendidikan_terakhir: string;
    instansi: string;
    bidang_keahlian: string;
    masa_berlaku_sertifikat: string;
    pas_foto: File | null;
};

type TextField = Exclude<keyof BiodataForm, '_method' | 'pas_foto'>;

const textareaClass =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-base focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden md:text-sm';

function Section({ title, description, children }: { title: string; description: string; children: React.ReactNode }) {
    return (
        <section className="grid gap-6 border-b p-6 last:border-b-0 lg:grid-cols-[220px_1fr]">
            <div>
                <h2 className="font-semibold">{title}</h2>
                <p className="text-muted-foreground mt-1 text-sm">{description}</p>
            </div>

            <div className="grid gap-6">{children}</div>
        </section>
    );
}

export default function Biodata({ asesor, options }: BiodataProps) {
    const { flash } = usePage<SharedData>().props;
    const getInitials = useInitials();

    const { data, setData, post, errors, processing } = useForm<BiodataForm>({
        _method: 'put',
        nik: asesor.nik ?? '',
        tempat_lahir: asesor.tempat_lahir ?? '',
        tgl_lahir: asesor.tgl_lahir ?? '',
        jenis_kelamin: asesor.jenis_kelamin ?? '',
        no_telp: asesor.no_telp ?? '',
        alamat: asesor.alamat ?? '',
        provinsi: asesor.provinsi ?? '',
        kota: asesor.kota ?? '',
        kecamatan: asesor.kecamatan ?? '',
        kelurahan: asesor.kelurahan ?? '',
        pendidikan_terakhir: asesor.pendidikan_terakhir ?? '',
        instansi: asesor.instansi ?? '',
        bidang_keahlian: asesor.bidang_keahlian ?? '',
        masa_berlaku_sertifikat: asesor.masa_berlaku_sertifikat ?? '',
        pas_foto: null,
    });

    // Pratinjau foto yang baru dipilih sebelum diunggah.
    const previewUrl = useMemo(() => (data.pas_foto ? URL.createObjectURL(data.pas_foto) : null), [data.pas_foto]);

    useEffect(() => {
        return () => {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
            }
        };
    }, [previewUrl]);

    const fotoUrl = previewUrl ?? asesor.foto_url;

    const field = (key: TextField) => ({
        id: key,
        value: data[key],
        onChange: (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => setData(key, e.target.value),
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        // File hanya bisa dikirim lewat POST multipart; _method membuat Laravel memprosesnya sebagai PUT.
        post(route('asesor.biodata.update'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => setData('pas_foto', null),
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Biodata Asesor" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Biodata Asesor</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Lengkapi data diri Anda. Data ini dipakai untuk penugasan dan dicetak pada Kartu Tanda Asesor.
                        </p>
                    </div>

                    <Button asChild variant={asesor.lengkap ? 'default' : 'outline'} className="shrink-0">
                        <Link href={route('asesor.kartu')}>
                            <IdCard />
                            Kartu Tanda Asesor
                        </Link>
                    </Button>
                </div>

                {flash.success && (
                    <Alert className="border-emerald-500/40 bg-emerald-50 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                        <AlertDescription>{flash.success}</AlertDescription>
                    </Alert>
                )}

                {!asesor.lengkap && !flash.success && (
                    <Alert className="border-amber-500/40 bg-amber-50 text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                        <Info className="size-4" />
                        <AlertDescription>
                            Biodata Anda belum lengkap. Isi semua kolom bertanda wajib dan unggah pas foto agar Kartu Tanda Asesor dapat dibuat.
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={submit} className="bg-card max-w-5xl rounded-xl border shadow-xs">
                    <Section
                        title="Identitas Asesor"
                        description="Nama dan nomor registrasi mengikuti sertifikat. Hubungi admin LSP bila ada yang keliru."
                    >
                        <div className="grid gap-6 md:grid-cols-3">
                            <div className="grid gap-2">
                                <Label htmlFor="nama">Nama lengkap</Label>
                                <Input id="nama" value={asesor.nama} disabled readOnly />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="kode_asesor">Kode asesor</Label>
                                <Input id="kode_asesor" value={asesor.kode_asesor ?? '-'} disabled readOnly className="font-mono" />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="no_reg">No. Registrasi / MET</Label>
                                <Input id="no_reg" value={asesor.no_reg} disabled readOnly />
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="bidang_keahlian">Bidang keahlian *</Label>
                                <Input {...field('bidang_keahlian')} required autoComplete="off" placeholder="Contoh: Teknologi Informasi" />
                                <InputError message={errors.bidang_keahlian} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="masa_berlaku_sertifikat">Sertifikat asesor berlaku s.d. *</Label>
                                <DateInput {...field('masa_berlaku_sertifikat')} required />
                                <p className="text-muted-foreground text-xs">Tanggal akhir masa berlaku sertifikat asesor (MET) dari BNSP.</p>
                                <InputError message={errors.masa_berlaku_sertifikat} />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="instansi">Asal instansi / perusahaan</Label>
                            <Input {...field('instansi')} autoComplete="organization" placeholder="Opsional" />
                            <InputError message={errors.instansi} />
                        </div>
                    </Section>

                    <Section title="Pas Foto" description="Dicetak pada Kartu Tanda Asesor. Format JPG/PNG, maksimal 2 MB, latar polos, rasio 3×4.">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                            <div
                                className={cn(
                                    'bg-muted flex aspect-[3/4] w-28 shrink-0 items-center justify-center overflow-hidden rounded-lg border',
                                    errors.pas_foto && 'border-destructive',
                                )}
                            >
                                {fotoUrl ? (
                                    <img src={fotoUrl} alt={`Pas foto ${asesor.nama}`} className="size-full object-cover" />
                                ) : (
                                    <span className="text-muted-foreground text-2xl font-semibold">{getInitials(asesor.nama)}</span>
                                )}
                            </div>

                            <div className="grid flex-1 gap-2">
                                <Label htmlFor="pas_foto">{asesor.foto_url ? 'Ganti pas foto' : 'Unggah pas foto *'}</Label>
                                <Input
                                    // URL foto berubah setelah unggah berhasil, sehingga input file ikut dikosongkan.
                                    key={asesor.foto_url ?? 'kosong'}
                                    id="pas_foto"
                                    type="file"
                                    accept="image/jpeg,image/png"
                                    onChange={(e) => setData('pas_foto', e.target.files?.[0] ?? null)}
                                    required={!asesor.foto_url}
                                    className="cursor-pointer"
                                />
                                <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                                    <ImageUp className="size-3.5" />
                                    {data.pas_foto
                                        ? `${data.pas_foto.name} akan diunggah saat biodata disimpan.`
                                        : asesor.foto_url
                                          ? 'Biarkan kosong untuk tetap memakai foto saat ini.'
                                          : 'Belum ada pas foto.'}
                                </p>
                                <InputError message={errors.pas_foto} />
                            </div>
                        </div>
                    </Section>

                    <Section title="Data Pribadi" description="Sesuai KTP. NIK dipakai sebagai identitas unik asesor.">
                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="nik">NIK *</Label>
                                <Input
                                    {...field('nik')}
                                    onChange={(e) => setData('nik', e.target.value.replace(/\D/g, '').slice(0, 16))}
                                    inputMode="numeric"
                                    required
                                    autoComplete="off"
                                    placeholder="16 digit sesuai KTP"
                                />
                                <InputError message={errors.nik} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="jenis_kelamin">Jenis kelamin *</Label>
                                <Select value={data.jenis_kelamin} onValueChange={(value) => setData('jenis_kelamin', value)}>
                                    <SelectTrigger id="jenis_kelamin">
                                        <SelectValue placeholder="Pilih jenis kelamin" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(options.jenis_kelamin).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.jenis_kelamin} />
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="tempat_lahir">Tempat lahir *</Label>
                                <Input {...field('tempat_lahir')} required autoComplete="off" placeholder="Contoh: Surabaya" />
                                <InputError message={errors.tempat_lahir} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="tgl_lahir">Tanggal lahir *</Label>
                                <DateInput {...field('tgl_lahir')} required autoComplete="bday" />
                                <InputError message={errors.tgl_lahir} />
                            </div>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="pendidikan_terakhir">Pendidikan terakhir *</Label>
                                <Select value={data.pendidikan_terakhir} onValueChange={(value) => setData('pendidikan_terakhir', value)}>
                                    <SelectTrigger id="pendidikan_terakhir">
                                        <SelectValue placeholder="Pilih jenjang pendidikan" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(options.pendidikan).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.pendidikan_terakhir} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="no_telp">No. telepon / WhatsApp *</Label>
                                <Input {...field('no_telp')} type="tel" required autoComplete="tel" placeholder="Contoh: 081234567890" />
                                <InputError message={errors.no_telp} />
                            </div>
                        </div>
                    </Section>

                    <Section title="Alamat" description="Alamat domisili saat ini untuk keperluan korespondensi.">
                        <div className="grid gap-2">
                            <Label htmlFor="alamat">Detail alamat *</Label>
                            <textarea {...field('alamat')} required rows={2} className={textareaClass} placeholder="Nama jalan, nomor rumah, RT/RW" />
                            <InputError message={errors.alamat} />
                        </div>

                        <PilihWilayah
                            value={{ provinsi: data.provinsi, kota: data.kota, kecamatan: data.kecamatan, kelurahan: data.kelurahan }}
                            onChange={(perubahan) => setData((current) => ({ ...current, ...perubahan }))}
                            errors={errors}
                        />
                    </Section>

                    <div className="bg-muted/30 flex flex-col gap-3 rounded-b-xl border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-muted-foreground flex items-center gap-1.5 text-xs">
                            {asesor.lengkap && <CircleCheck className="size-3.5 text-emerald-600" />}
                            {asesor.diperbarui_pada ? `Terakhir diperbarui ${asesor.diperbarui_pada}` : 'Biodata belum pernah disimpan.'}
                        </p>

                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="animate-spin" />}
                            Simpan biodata
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
