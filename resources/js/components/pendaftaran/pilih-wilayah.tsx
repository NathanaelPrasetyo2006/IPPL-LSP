import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { CircleAlert, LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

export type TingkatWilayah = 'provinsi' | 'kota' | 'kecamatan' | 'kelurahan';

interface Opsi {
    id: string;
    nama: string;
}

interface PilihWilayahProps {
    /** Nama wilayah yang tersimpan (bukan kode). */
    value: Record<TingkatWilayah, string>;
    onChange: (perubahan: Partial<Record<TingkatWilayah, string>>) => void;
    errors: Partial<Record<string, string>>;
    /** Akhiran nama kolom bila satu halaman punya lebih dari satu alamat, mis. "_kantor" untuk provinsi_kantor. */
    akhiran?: string;
    /** Alamat opsional boleh dikosongkan lagi setelah dipilih. */
    wajib?: boolean;
}

const URUTAN: TingkatWilayah[] = ['provinsi', 'kota', 'kecamatan', 'kelurahan'];

const LABEL: Record<TingkatWilayah, string> = {
    provinsi: 'Provinsi',
    kota: 'Kota / kabupaten',
    kecamatan: 'Kecamatan',
    kelurahan: 'Kelurahan / desa',
};

/** URL daftar anak dari wilayah induk; provinsi tidak punya induk. */
function urlDaftar(tingkat: TingkatWilayah, idInduk: string): string {
    return tingkat === 'provinsi' ? route('wilayah.provinsi') : route(`wilayah.${tingkat}`, idInduk);
}

async function ambil(url: string): Promise<Opsi[]> {
    const response = await fetch(url, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error('Data wilayah tidak dapat dimuat.');
    }

    return response.json();
}

const cocok = (daftar: Opsi[], nama: string) => daftar.find((opsi) => opsi.nama.toLowerCase() === nama.trim().toLowerCase());

const KOSONG: Record<TingkatWilayah, string> = { provinsi: '', kota: '', kecamatan: '', kelurahan: '' };

/**
 * Alamat bertingkat provinsi → kab/kota → kecamatan → kelurahan dari API wilayah.
 * Yang disimpan tetap nama wilayah; bila API tidak bisa dihubungi, isian beralih ke ketik manual.
 */
export default function PilihWilayah({ value, onChange, errors, akhiran = '', wajib = true }: PilihWilayahProps) {
    const [daftar, setDaftar] = useState<Record<TingkatWilayah, Opsi[]>>({ provinsi: [], kota: [], kecamatan: [], kelurahan: [] });
    const [terpilih, setTerpilih] = useState<Record<TingkatWilayah, string>>(KOSONG);
    const [memuat, setMemuat] = useState<TingkatWilayah | null>('provinsi');
    const [gagal, setGagal] = useState(false);

    // Muat provinsi, lalu cocokkan nama wilayah yang sudah tersimpan di profil tingkat demi tingkat.
    useEffect(() => {
        let batal = false;

        const muatAwal = async () => {
            try {
                const hasil: Partial<Record<TingkatWilayah, Opsi[]>> = {};
                const id: Partial<Record<TingkatWilayah, string>> = {};
                let induk = '';

                for (const tingkat of URUTAN) {
                    setMemuat(tingkat);
                    hasil[tingkat] = await ambil(urlDaftar(tingkat, induk));

                    const ketemu = value[tingkat] ? cocok(hasil[tingkat], value[tingkat]) : undefined;

                    if (!ketemu) {
                        break;
                    }

                    id[tingkat] = induk = ketemu.id;
                }

                if (!batal) {
                    setDaftar((current) => ({ ...current, ...hasil }));
                    setTerpilih((current) => ({ ...current, ...id }));
                }
            } catch {
                if (!batal) {
                    setGagal(true);
                }
            } finally {
                if (!batal) {
                    setMemuat(null);
                }
            }
        };

        muatAwal();

        return () => {
            batal = true;
        };
        // Hanya saat pertama dibuka; perubahan berikutnya ditangani pilih().
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const pilih = async (tingkat: TingkatWilayah, id: string) => {
        const indeks = URUTAN.indexOf(tingkat);
        const bawahnya = URUTAN.slice(indeks + 1);
        const nama = daftar[tingkat].find((opsi) => opsi.id === id)?.nama ?? '';

        // Mengganti wilayah mengosongkan semua tingkat di bawahnya.
        onChange({ [tingkat]: nama, ...Object.fromEntries(bawahnya.map((t) => [t, ''])) });
        setTerpilih((current) => ({ ...current, [tingkat]: id, ...Object.fromEntries(bawahnya.map((t) => [t, ''])) }));
        setDaftar((current) => ({ ...current, ...Object.fromEntries(bawahnya.map((t) => [t, []])) }));

        const berikutnya = bawahnya[0];

        if (!berikutnya) {
            return;
        }

        setMemuat(berikutnya);

        try {
            const hasil = await ambil(urlDaftar(berikutnya, id));
            setDaftar((current) => ({ ...current, [berikutnya]: hasil }));
        } catch {
            setGagal(true);
        } finally {
            setMemuat(null);
        }
    };

    const kosongkan = () => {
        onChange(KOSONG);
        setTerpilih(KOSONG);
        setDaftar((current) => ({ ...current, kota: [], kecamatan: [], kelurahan: [] }));
    };

    const label = (tingkat: TingkatWilayah) => (
        <Label htmlFor={tingkat + akhiran}>
            {LABEL[tingkat]}
            {wajib && <span className="text-red-600"> *</span>}
        </Label>
    );

    if (gagal) {
        return (
            <div className="grid gap-3">
                <p className="flex items-start gap-2 rounded-lg border border-amber-500/40 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                    <CircleAlert className="mt-0.5 size-4 shrink-0" />
                    Daftar wilayah sedang tidak dapat dimuat. Silakan ketik nama wilayah secara manual.
                </p>

                <div className="grid gap-6 md:grid-cols-2">
                    {URUTAN.map((tingkat) => (
                        <div key={tingkat} className="grid content-start gap-2">
                            {label(tingkat)}
                            <Input
                                id={tingkat + akhiran}
                                value={value[tingkat]}
                                onChange={(e) => onChange({ [tingkat]: e.target.value })}
                                required={wajib}
                            />
                            <InputError message={errors[tingkat + akhiran]} />
                        </div>
                    ))}
                </div>
            </div>
        );
    }

    return (
        <div className="grid gap-3">
            <div className="grid gap-6 md:grid-cols-2">
                {URUTAN.map((tingkat, indeks) => {
                    const induk = URUTAN[indeks - 1];
                    const terkunci = (induk !== undefined && !terpilih[induk]) || memuat === tingkat;

                    return (
                        <div key={tingkat} className="grid content-start gap-2">
                            {label(tingkat)}

                            <Select value={terpilih[tingkat]} onValueChange={(id) => pilih(tingkat, id)} disabled={terkunci}>
                                <SelectTrigger id={tingkat + akhiran} aria-invalid={!!errors[tingkat + akhiran]}>
                                    {memuat === tingkat ? (
                                        <span className="text-muted-foreground inline-flex items-center gap-2">
                                            <LoaderCircle className="size-4 animate-spin" />
                                            Memuat…
                                        </span>
                                    ) : (
                                        <SelectValue
                                            placeholder={
                                                induk && !terpilih[induk]
                                                    ? `Pilih ${LABEL[induk].toLowerCase()} dulu`
                                                    : `Pilih ${LABEL[tingkat].toLowerCase()}`
                                            }
                                        />
                                    )}
                                </SelectTrigger>

                                <SelectContent className="max-h-72">
                                    {daftar[tingkat].map((opsi) => (
                                        <SelectItem key={opsi.id} value={opsi.id}>
                                            {opsi.nama}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <InputError message={errors[tingkat + akhiran]} />
                        </div>
                    );
                })}
            </div>

            {!wajib && terpilih.provinsi && (
                <Button type="button" variant="ghost" size="sm" onClick={kosongkan} className="text-muted-foreground justify-self-start">
                    Kosongkan wilayah
                </Button>
            )}
        </div>
    );
}
