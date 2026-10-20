import InputError from '@/components/input-error';
import PilihLokasi from '@/components/pendaftaran/pilih-lokasi';
import PilihWilayah, { type TingkatWilayah } from '@/components/pendaftaran/pilih-wilayah';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { DateInput } from '@/components/ui/date-input';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type JenisDokumenDef, type Tuk } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { BadgeCheck, ChevronDown, ExternalLink, FileUp, Info, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pendaftaran Sertifikasi', href: '/sertifikasi' },
    { title: 'Formulir APL-01', href: '#' },
];

type Profil = {
    nama_lengkap: string;
    nik: string;
    tempat_lahir: string;
    tgl_lahir: string;
    jenis_kelamin: string;
    id_negara: string;
    alamat_ktp: string;
    provinsi_ktp: string;
    kota_ktp: string;
    kecamatan_ktp: string;
    kelurahan_ktp: string;
    alamat_domisili: string;
    provinsi: string;
    kota: string;
    kecamatan: string;
    kelurahan: string;
    kode_pos: string;
    no_telp: string;
    jenjang_pendidikan: string;
    nama_sekolah: string;
    pekerjaan: string;
    nama_institusi: string;
    jabatan: string;
    alamat_kantor: string;
    provinsi_kantor: string;
    kota_kantor: string;
    kecamatan_kantor: string;
    kelurahan_kantor: string;
    kode_pos_kantor: string;
    telp_kantor: string;
    email_kantor: string;
};

type Apl01Form = Profil & {
    kebangsaan: 'wni' | 'wna';
    tujuan_asesmen: string;
    tujuan_asesmen_lainnya: string;
    id_tuk: string;
    pernyataan: boolean;
    dokumen: Record<string, File | null>;
};

interface CreateProps {
    periode: { id_periode: number; ulid: string; nama: string; tanggal_tutup: string };
    skema: {
        id_skema: number;
        ulid: string;
        judul: string;
        nomor: string;
        unit_kompetensi: { id_unit_kompetensi: number; kode: string; judul: string }[];
    };
    /** Lokasi uji yang tersedia untuk skema ini pada periode terpilih. */
    lokasi: Tuk[];
    kodeAsesi: string | null;
    email: string;
    profil: Partial<Record<keyof Profil, string | number | null>>;
    /** URL dokumen yang sudah tersimpan per jenis: identitas dari profil, dokumen lain dari permohonan yang diperbaiki. */
    dokumenTersimpan: Record<string, string | null>;
    /** Terisi saat memperbaiki permohonan yang ditolak; null untuk pendaftaran baru. */
    perbaikan: {
        ulid: string;
        nomor: string;
        catatan_verifikasi: string | null;
        tujuan_asesmen: string;
        tujuan_asesmen_lainnya: string | null;
        id_tuk: number | null;
    } | null;
    options: {
        /** Negara selain Indonesia, untuk WNA. */
        negara: Record<string, string>;
        id_indonesia: number | null;
        jenis_kelamin: Record<string, string>;
        pendidikan: Record<string, string>;
        tujuan_asesmen: Record<string, string>;
        dokumen: JenisDokumenDef[];
    };
}

const TINGKAT_WILAYAH: TingkatWilayah[] = ['provinsi', 'kota', 'kecamatan', 'kelurahan'];

/** Nilai wilayah satu alamat; akhiran "" untuk domisili, "_ktp" atau "_kantor" untuk alamat lain. */
function wilayahDari(data: Record<string, unknown>, akhiran: string): Record<TingkatWilayah, string> {
    return Object.fromEntries(TINGKAT_WILAYAH.map((tingkat) => [tingkat, String(data[tingkat + akhiran] ?? '')])) as Record<TingkatWilayah, string>;
}

/** Ubah perubahan wilayah ke nama kolomnya, mis. kota → kota_ktp. */
function kolomWilayah(perubahan: Partial<Record<TingkatWilayah, string>>, akhiran: string): Record<string, string> {
    return Object.fromEntries(Object.entries(perubahan).map(([tingkat, nama]) => [tingkat + akhiran, nama ?? '']));
}

const textareaClass =
    'border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border px-3 py-2 text-base focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-hidden md:text-sm';

function Field({
    id,
    label,
    error,
    hint,
    required,
    className,
    children,
}: {
    id: string;
    label: string;
    error?: string;
    hint?: string;
    required?: boolean;
    className?: string;
    children: React.ReactNode;
}) {
    return (
        <div className={cn('grid content-start gap-2', className)}>
            <Label htmlFor={id}>
                {label}
                {required && <span className="text-red-600"> *</span>}
            </Label>
            {children}
            {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}

function Section({ nomor, title, description, children }: { nomor: string; title: string; description: string; children: React.ReactNode }) {
    return (
        <section className="grid gap-6 border-b p-6 last:border-b-0 lg:grid-cols-[240px_1fr]">
            <div>
                <span className="text-muted-foreground text-xs font-semibold tracking-wide uppercase">{nomor}</span>
                <h2 className="mt-0.5 font-semibold">{title}</h2>
                <p className="text-muted-foreground mt-1 text-sm">{description}</p>
            </div>
            <div className="grid gap-6">{children}</div>
        </section>
    );
}

function SelectField({
    id,
    value,
    onChange,
    options,
    placeholder,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: Record<string, string>;
    placeholder: string;
}) {
    return (
        <Select value={value} onValueChange={onChange}>
            <SelectTrigger id={id}>
                <SelectValue placeholder={placeholder} />
            </SelectTrigger>
            <SelectContent>
                {Object.entries(options).map(([key, label]) => (
                    <SelectItem key={key} value={key}>
                        {label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export default function CreateApl01({ periode, skema, lokasi, kodeAsesi, email, profil, dokumenTersimpan, perbaikan, options }: CreateProps) {
    const awal = (key: keyof Profil) => (profil[key] === null || profil[key] === undefined ? '' : String(profil[key]));

    // Profil lama belum punya wilayah KTP; bila alamat KTP sama dengan domisili, wilayah domisili dipakai.
    const wilayahKtpKosong = TINGKAT_WILAYAH.every((tingkat) => !awal(`${tingkat}_ktp`));
    const awalKtp = (tingkat: TingkatWilayah) =>
        wilayahKtpKosong && awal('alamat_ktp') !== '' && awal('alamat_ktp') === awal('alamat_domisili') ? awal(tingkat) : awal(`${tingkat}_ktp`);

    const { data, setData, post, errors, processing, transform } = useForm<Apl01Form>({
        // Profil tanpa negara atau berkebangsaan Indonesia dianggap WNI.
        kebangsaan: !awal('id_negara') || awal('id_negara') === String(options.id_indonesia) ? 'wni' : 'wna',
        nama_lengkap: awal('nama_lengkap'),
        nik: awal('nik'),
        tempat_lahir: awal('tempat_lahir'),
        tgl_lahir: awal('tgl_lahir'),
        jenis_kelamin: awal('jenis_kelamin'),
        id_negara: awal('id_negara'),
        alamat_ktp: awal('alamat_ktp'),
        provinsi_ktp: awalKtp('provinsi'),
        kota_ktp: awalKtp('kota'),
        kecamatan_ktp: awalKtp('kecamatan'),
        kelurahan_ktp: awalKtp('kelurahan'),
        alamat_domisili: awal('alamat_domisili'),
        provinsi: awal('provinsi'),
        kota: awal('kota'),
        kecamatan: awal('kecamatan'),
        kelurahan: awal('kelurahan'),
        kode_pos: awal('kode_pos'),
        no_telp: awal('no_telp'),
        jenjang_pendidikan: awal('jenjang_pendidikan'),
        nama_sekolah: awal('nama_sekolah'),
        pekerjaan: awal('pekerjaan'),
        nama_institusi: awal('nama_institusi'),
        jabatan: awal('jabatan'),
        alamat_kantor: awal('alamat_kantor'),
        provinsi_kantor: awal('provinsi_kantor'),
        kota_kantor: awal('kota_kantor'),
        kecamatan_kantor: awal('kecamatan_kantor'),
        kelurahan_kantor: awal('kelurahan_kantor'),
        kode_pos_kantor: awal('kode_pos_kantor'),
        telp_kantor: awal('telp_kantor'),
        email_kantor: awal('email_kantor'),
        tujuan_asesmen: perbaikan?.tujuan_asesmen ?? 'sertifikasi',
        tujuan_asesmen_lainnya: perbaikan?.tujuan_asesmen_lainnya ?? '',
        // Lokasi sebelumnya dipakai lagi bila masih tersedia; bila hanya ada satu lokasi, langsung terpilih.
        id_tuk: lokasi.some((tuk) => tuk.id_tuk === perbaikan?.id_tuk)
            ? String(perbaikan?.id_tuk)
            : lokasi.length === 1
              ? lokasi[0].id_tuk.toString()
              : '',
        pernyataan: false,
        dokumen: {},
    });

    const [domisiliSama, setDomisiliSama] = useState(
        data.alamat_ktp !== '' &&
            data.alamat_ktp === data.alamat_domisili &&
            TINGKAT_WILAYAH.every((tingkat) => wilayahDari(data, '_ktp')[tingkat] === data[tingkat]),
    );

    // Domisili yang sama dengan KTP mengikuti detail alamat dan wilayah KTP.
    const salinKtpKeDomisili = (current: Apl01Form): Apl01Form => ({
        ...current,
        alamat_domisili: current.alamat_ktp,
        ...kolomWilayah(wilayahDari(current, '_ktp'), ''),
    });
    const [unitTerbuka, setUnitTerbuka] = useState(false);

    const err = errors as Partial<Record<string, string>>;
    const jumlahError = Object.keys(errors).length;

    const text = (key: keyof Profil) => ({
        id: key,
        value: data[key],
        onChange: (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => setData(key, e.target.value),
    });

    const digits = (key: keyof Profil, max: number) => ({
        id: key,
        value: data[key],
        inputMode: 'numeric' as const,
        onChange: (e: React.ChangeEvent<HTMLInputElement>) => setData(key, e.target.value.replace(/\D/g, '').slice(0, max)),
    });

    const setDokumen = (jenis: string, file: File | null) => setData('dokumen', { ...data.dokumen, [jenis]: file });

    // Kirim hanya file yang benar-benar dipilih.
    transform((current) => ({
        ...(domisiliSama ? salinKtpKeDomisili(current) : current),
        dokumen: Object.fromEntries(Object.entries(current.dokumen).filter(([, file]) => file !== null)),
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(perbaikan ? route('sertifikasi.perbaiki.store', perbaikan.ulid) : route('sertifikasi.store', [periode.ulid, skema.ulid]), {
            forceFormData: true,
            preserveScroll: (page) => Object.keys(page.props.errors).length > 0,
            onError: () => window.scrollTo({ top: 0, behavior: 'smooth' }),
        });
    };

    const kelompokDokumen = [
        {
            judul: 'Bukti administratif',
            keterangan: 'Disimpan di profil Anda dan dipakai ulang pada permohonan berikutnya.',
            items: options.dokumen.filter((d) => d.identitas),
        },
        {
            judul: 'Bukti persyaratan dasar',
            keterangan: 'Khusus untuk permohonan ini.',
            items: options.dokumen.filter((d) => !d.identitas),
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={perbaikan ? `Perbaiki ${perbaikan.nomor}` : 'Formulir APL-01'} />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div>
                    <p className="text-muted-foreground text-sm font-medium">FR-APL-01</p>
                    <h1 className="text-2xl font-bold tracking-tight md:text-3xl">
                        {perbaikan ? `Perbaiki Permohonan ${perbaikan.nomor}` : 'Permohonan Sertifikasi Kompetensi'}
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Skema <span className="text-foreground font-medium">{skema.judul}</span> · {periode.nama} · pendaftaran ditutup{' '}
                        <span className="text-foreground font-medium">{periode.tanggal_tutup}</span>
                        {kodeAsesi && (
                            <>
                                {' '}
                                · kode asesi <span className="text-foreground font-mono font-medium">{kodeAsesi}</span>
                            </>
                        )}
                    </p>
                </div>

                {perbaikan && (
                    <Alert className="max-w-5xl border-red-500/40 bg-red-50 text-red-900 dark:bg-red-950/40 dark:text-red-200">
                        <AlertDescription>
                            <p className="font-medium">Permohonan ini ditolak. Perbaiki sesuai catatan verifikator, lalu ajukan ulang.</p>
                            {perbaikan.catatan_verifikasi && <p className="mt-1 whitespace-pre-line">{perbaikan.catatan_verifikasi}</p>}
                            <p className="mt-1 text-xs opacity-80">Dokumen yang sudah tersimpan tetap dipakai; unggah hanya yang perlu diganti.</p>
                        </AlertDescription>
                    </Alert>
                )}

                {jumlahError > 0 && (
                    <Alert variant="destructive" className="max-w-5xl">
                        <AlertDescription>
                            Ada {jumlahError} isian yang perlu diperbaiki. Periksa kolom bertanda merah di bawah.
                            {Object.keys(errors).some((key) => key.startsWith('dokumen.')) && ' File dokumen perlu dipilih ulang.'}
                        </AlertDescription>
                    </Alert>
                )}

                <form onSubmit={submit} className="bg-card max-w-5xl rounded-xl border shadow-xs">
                    <Section
                        nomor="Bagian 1a"
                        title="Data Pribadi"
                        description="Isi sesuai KTP. Data ini tersimpan di profil Anda untuk permohonan berikutnya."
                    >
                        <div className="grid gap-6 md:grid-cols-2">
                            <Field id="nama_lengkap" label="Nama lengkap" error={err.nama_lengkap} required>
                                <Input {...text('nama_lengkap')} required autoComplete="name" />
                            </Field>

                            <Field id="nik" label="NIK" error={err.nik} hint="16 digit sesuai KTP." required>
                                <Input {...digits('nik', 16)} required autoComplete="off" />
                            </Field>
                        </div>

                        <div className="grid gap-6 md:grid-cols-3">
                            <Field id="tempat_lahir" label="Tempat lahir" error={err.tempat_lahir} required>
                                <Input {...text('tempat_lahir')} required />
                            </Field>

                            <Field id="tgl_lahir" label="Tanggal lahir" error={err.tgl_lahir} required>
                                <DateInput {...text('tgl_lahir')} required autoComplete="bday" />
                            </Field>

                            <Field id="jenis_kelamin" label="Jenis kelamin" error={err.jenis_kelamin} required>
                                <SelectField
                                    id="jenis_kelamin"
                                    value={data.jenis_kelamin}
                                    onChange={(value) => setData('jenis_kelamin', value)}
                                    options={options.jenis_kelamin}
                                    placeholder="Pilih"
                                />
                            </Field>
                        </div>

                        <div className="grid gap-6 md:grid-cols-2">
                            <div className="grid content-start gap-3">
                                <Field id="kebangsaan" label="Kebangsaan" error={err.kebangsaan} required>
                                    <div className="grid grid-cols-2 gap-2" role="radiogroup">
                                        {(
                                            [
                                                ['wni', 'WNI'],
                                                ['wna', 'WNA'],
                                            ] as const
                                        ).map(([value, label]) => (
                                            <label
                                                key={value}
                                                className={cn(
                                                    'hover:bg-muted/50 flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2 text-sm',
                                                    data.kebangsaan === value && 'border-primary bg-muted/50',
                                                )}
                                            >
                                                <input
                                                    type="radio"
                                                    name="kebangsaan"
                                                    value={value}
                                                    checked={data.kebangsaan === value}
                                                    onChange={() => {
                                                        setData('kebangsaan', value);
                                                        setData(
                                                            'id_negara',
                                                            value === 'wni'
                                                                ? String(options.id_indonesia ?? '')
                                                                : data.id_negara === String(options.id_indonesia)
                                                                  ? ''
                                                                  : data.id_negara,
                                                        );
                                                    }}
                                                    className="accent-primary size-4"
                                                />
                                                {label}
                                            </label>
                                        ))}
                                    </div>
                                </Field>

                                {data.kebangsaan === 'wna' && (
                                    <Field id="id_negara" label="Negara asal" error={err.id_negara} required>
                                        <SelectField
                                            id="id_negara"
                                            value={data.id_negara}
                                            onChange={(value) => setData('id_negara', value)}
                                            options={options.negara}
                                            placeholder="Pilih negara"
                                        />
                                    </Field>
                                )}
                            </div>

                            <Field id="no_telp" label="No. HP / WhatsApp" error={err.no_telp} required>
                                <Input {...text('no_telp')} type="tel" required autoComplete="tel" />
                            </Field>
                        </div>

                        <Field id="email" label="Email" hint="Email akun Anda. Ubah lewat menu Pengaturan bila perlu.">
                            <Input id="email" value={email} disabled readOnly />
                        </Field>

                        <Field id="alamat_ktp" label="Alamat sesuai KTP" error={err.alamat_ktp} required>
                            <textarea {...text('alamat_ktp')} rows={2} required className={textareaClass} placeholder="Jalan, nomor, RT/RW" />
                        </Field>

                        <PilihWilayah
                            akhiran="_ktp"
                            value={wilayahDari(data, '_ktp')}
                            onChange={(perubahan) => setData((current) => ({ ...current, ...kolomWilayah(perubahan, '_ktp') }))}
                            errors={err}
                        />

                        <div className="grid gap-2">
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="domisili_sama"
                                    checked={domisiliSama}
                                    onCheckedChange={(checked) => {
                                        setDomisiliSama(checked === true);

                                        if (checked === true) {
                                            setData(salinKtpKeDomisili);
                                        }
                                    }}
                                />
                                <Label htmlFor="domisili_sama" className="font-normal">
                                    Alamat domisili sama dengan alamat KTP
                                </Label>
                            </div>

                            {domisiliSama && <InputError message={err.alamat_domisili} />}
                        </div>

                        {!domisiliSama && (
                            <>
                                <Field id="alamat_domisili" label="Alamat domisili" error={err.alamat_domisili} required>
                                    <textarea
                                        {...text('alamat_domisili')}
                                        rows={2}
                                        required
                                        className={textareaClass}
                                        placeholder="Jalan, nomor, RT/RW"
                                    />
                                </Field>

                                <PilihWilayah
                                    value={wilayahDari(data, '')}
                                    onChange={(perubahan) => setData((current) => ({ ...current, ...perubahan }))}
                                    errors={err}
                                />
                            </>
                        )}

                        <div className="grid gap-6 md:grid-cols-2">
                            <Field id="kode_pos" label="Kode pos" error={err.kode_pos} required>
                                <Input {...digits('kode_pos', 5)} required autoComplete="postal-code" />
                            </Field>
                        </div>

                        <div className="grid gap-6 md:grid-cols-3">
                            <Field id="jenjang_pendidikan" label="Pendidikan terakhir" error={err.jenjang_pendidikan} required>
                                <SelectField
                                    id="jenjang_pendidikan"
                                    value={data.jenjang_pendidikan}
                                    onChange={(value) => setData('jenjang_pendidikan', value)}
                                    options={options.pendidikan}
                                    placeholder="Pilih jenjang"
                                />
                            </Field>

                            <Field
                                id="nama_sekolah"
                                label="Nama sekolah / perguruan tinggi"
                                error={err.nama_sekolah}
                                required
                                className="md:col-span-2"
                            >
                                <Input {...text('nama_sekolah')} required />
                            </Field>
                        </div>

                        <Field id="pekerjaan" label="Pekerjaan" error={err.pekerjaan} hint="Contoh: Karyawan swasta, Mahasiswa, Wiraswasta." required>
                            <Input {...text('pekerjaan')} required />
                        </Field>
                    </Section>

                    <Section nomor="Bagian 1b" title="Data Pekerjaan Sekarang" description="Opsional. Kosongkan bila belum bekerja.">
                        <div className="grid gap-6 md:grid-cols-2">
                            <Field id="nama_institusi" label="Nama institusi / perusahaan" error={err.nama_institusi}>
                                <Input {...text('nama_institusi')} autoComplete="organization" />
                            </Field>

                            <Field id="jabatan" label="Jabatan" error={err.jabatan}>
                                <Input {...text('jabatan')} autoComplete="organization-title" />
                            </Field>
                        </div>

                        <Field id="alamat_kantor" label="Alamat kantor" error={err.alamat_kantor}>
                            <textarea {...text('alamat_kantor')} rows={2} className={textareaClass} placeholder="Jalan, nomor, nama gedung" />
                        </Field>

                        <PilihWilayah
                            akhiran="_kantor"
                            wajib={false}
                            value={wilayahDari(data, '_kantor')}
                            onChange={(perubahan) => setData((current) => ({ ...current, ...kolomWilayah(perubahan, '_kantor') }))}
                            errors={err}
                        />

                        <div className="grid gap-6 md:grid-cols-3">
                            <Field id="kode_pos_kantor" label="Kode pos kantor" error={err.kode_pos_kantor}>
                                <Input {...digits('kode_pos_kantor', 5)} />
                            </Field>

                            <Field id="telp_kantor" label="Telepon kantor" error={err.telp_kantor}>
                                <Input {...text('telp_kantor')} type="tel" />
                            </Field>

                            <Field id="email_kantor" label="Email kantor" error={err.email_kantor}>
                                <Input {...text('email_kantor')} type="email" />
                            </Field>
                        </div>
                    </Section>

                    <Section nomor="Bagian 2" title="Data Sertifikasi" description="Skema yang Anda pilih dan tujuan mengikuti asesmen.">
                        <div className="flex items-start gap-3 rounded-lg border bg-blue-50/50 p-4 dark:bg-blue-950/20">
                            <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                                <BadgeCheck className="size-5" />
                            </span>
                            <div className="min-w-0 flex-1">
                                <p className="font-semibold">{skema.judul}</p>
                                <p className="text-muted-foreground text-xs">{skema.nomor}</p>

                                <button
                                    type="button"
                                    onClick={() => setUnitTerbuka((open) => !open)}
                                    className="text-muted-foreground hover:text-foreground mt-2 inline-flex items-center gap-1 text-xs font-medium"
                                >
                                    <ChevronDown className={cn('size-3.5 transition-transform', unitTerbuka && 'rotate-180')} />
                                    {skema.unit_kompetensi.length} unit kompetensi
                                </button>

                                {unitTerbuka && (
                                    <ol className="mt-2 grid gap-1.5 text-sm">
                                        {skema.unit_kompetensi.map((unit, index) => (
                                            <li key={unit.id_unit_kompetensi} className="flex gap-2">
                                                <span className="text-muted-foreground w-5 shrink-0 tabular-nums">{index + 1}.</span>
                                                <span>
                                                    <span className="text-muted-foreground font-mono text-xs">{unit.kode}</span> {unit.judul}
                                                </span>
                                            </li>
                                        ))}
                                    </ol>
                                )}
                            </div>
                        </div>

                        <Field id="tujuan_asesmen" label="Tujuan asesmen" error={err.tujuan_asesmen} required>
                            <div className="grid gap-2 sm:grid-cols-2" role="radiogroup">
                                {Object.entries(options.tujuan_asesmen).map(([value, label]) => (
                                    <label
                                        key={value}
                                        className={cn(
                                            'hover:bg-muted/50 flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-2.5 text-sm',
                                            data.tujuan_asesmen === value && 'border-primary bg-muted/50',
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name="tujuan_asesmen"
                                            value={value}
                                            checked={data.tujuan_asesmen === value}
                                            onChange={() => setData('tujuan_asesmen', value)}
                                            className="accent-primary size-4"
                                        />
                                        {label}
                                    </label>
                                ))}
                            </div>
                        </Field>

                        <Field id="id_tuk" label="Lokasi uji (TUK)" error={err.id_tuk} hint="Tempat Anda akan mengikuti asesmen." required>
                            <PilihLokasi lokasi={lokasi} value={data.id_tuk} onChange={(value) => setData('id_tuk', value)} />
                        </Field>

                        {data.tujuan_asesmen === 'lainnya' && (
                            <Field id="tujuan_asesmen_lainnya" label="Sebutkan tujuan lainnya" error={err.tujuan_asesmen_lainnya} required>
                                <Input
                                    id="tujuan_asesmen_lainnya"
                                    value={data.tujuan_asesmen_lainnya}
                                    onChange={(e) => setData('tujuan_asesmen_lainnya', e.target.value)}
                                    required
                                />
                            </Field>
                        )}
                    </Section>

                    <Section
                        nomor="Bagian 3"
                        title="Bukti Kelengkapan Pemohon"
                        description="Unggah dokumen dalam format PDF/JPG/PNG. Dokumen disimpan di folder pribadi Anda dan hanya bisa dibuka oleh Anda dan admin LSP."
                    >
                        {kelompokDokumen.map(({ judul, keterangan, items }) => (
                            <div key={judul} className="grid gap-3">
                                <div>
                                    <h3 className="text-sm font-semibold">{judul}</h3>
                                    <p className="text-muted-foreground text-xs">{keterangan}</p>
                                </div>

                                <ul className="divide-y rounded-lg border">
                                    {items.map((def) => {
                                        const tersimpan = dokumenTersimpan[def.jenis] ?? null;
                                        const dipilih = data.dokumen[def.jenis] ?? null;
                                        const wajib = def.wajib && !tersimpan;
                                        const error = err[`dokumen.${def.jenis}`];

                                        return (
                                            <li
                                                key={def.jenis}
                                                className={cn(
                                                    'grid gap-2 p-3 sm:grid-cols-[1fr_auto] sm:items-center',
                                                    error && 'bg-red-50/50 dark:bg-red-950/20',
                                                )}
                                            >
                                                <div className="min-w-0">
                                                    <Label htmlFor={`dokumen-${def.jenis}`} className="text-sm">
                                                        {def.label}
                                                        {def.wajib ? (
                                                            <span className="text-red-600"> *</span>
                                                        ) : (
                                                            <span className="text-muted-foreground ml-1 text-xs font-normal">(opsional)</span>
                                                        )}
                                                    </Label>

                                                    <p className="text-muted-foreground mt-0.5 text-xs">
                                                        {dipilih ? (
                                                            <span className="text-foreground">{dipilih.name}</span>
                                                        ) : tersimpan ? (
                                                            <>
                                                                Sudah tersimpan ·{' '}
                                                                <a
                                                                    href={tersimpan}
                                                                    target="_blank"
                                                                    rel="noreferrer"
                                                                    className="inline-flex items-center gap-0.5 underline"
                                                                >
                                                                    lihat
                                                                    <ExternalLink className="size-3" />
                                                                </a>{' '}
                                                                · pilih "Ganti file" bila perlu diganti
                                                            </>
                                                        ) : (
                                                            <>
                                                                {def.accept.replaceAll('.', '').toUpperCase().replaceAll(',', ', ')} · maks.{' '}
                                                                {def.maks_mb} MB
                                                            </>
                                                        )}
                                                    </p>

                                                    <InputError message={error} className="mt-1" />
                                                </div>

                                                <label
                                                    htmlFor={`dokumen-${def.jenis}`}
                                                    className="border-input hover:bg-muted inline-flex cursor-pointer items-center justify-center gap-2 rounded-md border px-3 py-1.5 text-sm font-medium whitespace-nowrap"
                                                >
                                                    <FileUp className="size-4" />
                                                    {dipilih || tersimpan ? 'Ganti file' : 'Pilih file'}
                                                </label>

                                                <input
                                                    id={`dokumen-${def.jenis}`}
                                                    type="file"
                                                    accept={def.accept}
                                                    required={wajib && !dipilih}
                                                    onChange={(e) => setDokumen(def.jenis, e.target.files?.[0] ?? null)}
                                                    className="sr-only"
                                                />
                                            </li>
                                        );
                                    })}
                                </ul>
                            </div>
                        ))}
                    </Section>

                    <div className="grid gap-4 border-t p-6">
                        <div className="flex items-start gap-3 rounded-lg border border-sky-500/30 bg-sky-50 p-4 dark:bg-sky-950/40">
                            <Info className="mt-0.5 size-4 shrink-0 text-sky-600 dark:text-sky-400" />
                            <p className="text-sm text-sky-800 dark:text-sky-300">
                                Setelah diajukan, admin LSP akan memeriksa kelengkapan berkas. Hasil verifikasi (diterima/ditolak beserta catatan)
                                dikirim ke email Anda dan muncul di ikon lonceng.
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-start gap-3">
                                <Checkbox
                                    id="pernyataan"
                                    checked={data.pernyataan}
                                    onCheckedChange={(checked) => setData('pernyataan', checked === true)}
                                    className="mt-0.5"
                                />
                                <Label htmlFor="pernyataan" className="leading-snug font-normal">
                                    Saya menyatakan bahwa data dan dokumen yang saya isi adalah benar, dan bersedia mengikuti proses asesmen sesuai
                                    ketentuan LSP.
                                </Label>
                            </div>
                            <InputError message={err.pernyataan} />
                        </div>
                    </div>

                    <div className="bg-muted/30 flex flex-col-reverse gap-3 rounded-b-xl border-t px-6 py-4 sm:flex-row sm:items-center">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('sertifikasi.index')}>Batal</Link>
                        </Button>

                        <Button type="submit" disabled={processing || !data.pernyataan} className="sm:ml-auto">
                            {processing && <LoaderCircle className="animate-spin" />}
                            {perbaikan ? 'Ajukan ulang permohonan' : 'Ajukan permohonan'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
