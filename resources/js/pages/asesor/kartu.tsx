import KartuAsesorCanvas, { type KartuAsesorCanvasHandle } from '@/components/asesor/kartu-asesor-canvas';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type KartuAsesor } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Download, IdCard, Pencil, Printer, TriangleAlert } from 'lucide-react';
import { useRef, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Profil Asesor',
        href: '/asesor/biodata',
    },
    {
        title: 'Kartu Tanda Asesor',
        href: '/asesor/kartu',
    },
];

/** Label kolom biodata wajib, untuk menjelaskan apa yang masih kosong. */
const LABEL_BIODATA: Record<string, string> = {
    nik: 'NIK',
    tempat_lahir: 'Tempat lahir',
    tgl_lahir: 'Tanggal lahir',
    jenis_kelamin: 'Jenis kelamin',
    no_telp: 'No. telepon',
    alamat: 'Detail alamat',
    provinsi: 'Provinsi',
    kota: 'Kota/kabupaten',
    kecamatan: 'Kecamatan',
    kelurahan: 'Kelurahan/desa',
    pendidikan_terakhir: 'Pendidikan terakhir',
    bidang_keahlian: 'Bidang keahlian',
    masa_berlaku_sertifikat: 'Masa berlaku sertifikat',
    file_pas_foto: 'Pas foto',
};

interface KartuProps {
    asesor: KartuAsesor;
    lembaga: string;
    diterbitkan: string;
    biodata_wajib_kosong: string[];
}

function namaFile(noReg: string): string {
    return `kartu-asesor-${noReg
        .replace(/[^A-Za-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
        .toLowerCase()}.png`;
}

export default function Kartu({ asesor, lembaga, diterbitkan, biodata_wajib_kosong }: KartuProps) {
    const kartuRef = useRef<KartuAsesorCanvasHandle>(null);
    const [downloading, setDownloading] = useState(false);

    const download = async () => {
        setDownloading(true);

        try {
            const blob = await kartuRef.current?.toBlob();

            if (!blob) {
                return;
            }

            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = namaFile(asesor.no_reg);
            link.click();
            URL.revokeObjectURL(url);
        } finally {
            setDownloading(false);
        }
    };

    // Cetak di jendela terpisah dengan ukuran kertas pas kartu, sekaligus jalur "Simpan sebagai PDF".
    const print = () => {
        const dataUrl = kartuRef.current?.toDataUrl();
        const win = dataUrl ? window.open('', '_blank', 'width=720,height=520') : null;

        if (!dataUrl || !win) {
            return;
        }

        win.document.write(`<!doctype html>
<html><head><title>Kartu Tanda Asesor</title>
<style>
@page { size: 85.6mm 54mm; margin: 0; }
html, body { margin: 0; padding: 0; }
img { display: block; width: 85.6mm; height: 54mm; }
</style></head>
<body><img src="${dataUrl}" alt="Kartu Tanda Asesor"></body></html>`);
        win.document.close();

        const img = win.document.querySelector('img');
        const doPrint = () => {
            win.focus();
            win.print();
        };

        if (img?.complete) {
            doPrint();
        } else {
            img?.addEventListener('load', doPrint);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Kartu Tanda Asesor" />

            <div className="flex flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight md:text-3xl">Kartu Tanda Asesor</h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Kartu identitas asesor {lembaga}. Unduh sebagai gambar atau cetak langsung dalam ukuran kartu ID (85,6 × 54 mm).
                        </p>
                    </div>

                    <Button asChild variant="outline" className="shrink-0">
                        <Link href={route('asesor.biodata.edit')}>
                            <Pencil />
                            Ubah biodata
                        </Link>
                    </Button>
                </div>

                {!asesor.lengkap ? (
                    <div className="bg-card flex max-w-2xl flex-col items-start gap-4 rounded-xl border p-6 shadow-xs">
                        <span className="flex size-11 items-center justify-center rounded-xl bg-amber-500 text-white">
                            <IdCard className="size-5" />
                        </span>

                        <div>
                            <h2 className="font-semibold">Biodata belum lengkap</h2>
                            <p className="text-muted-foreground mt-1 text-sm">
                                Kartu Tanda Asesor dibuat dari biodata Anda. Lengkapi data berikut terlebih dahulu:
                            </p>

                            <ul className="mt-3 flex flex-wrap gap-2">
                                {biodata_wajib_kosong.map((kolom) => (
                                    <li key={kolom} className="bg-muted rounded-full px-2.5 py-0.5 text-xs font-medium">
                                        {LABEL_BIODATA[kolom] ?? kolom}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <Button asChild>
                            <Link href={route('asesor.biodata.edit')}>Lengkapi biodata</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        {asesor.kedaluwarsa && (
                            <Alert variant="destructive" className="max-w-3xl">
                                <TriangleAlert className="size-4" />
                                <AlertDescription>
                                    Sertifikat asesor Anda sudah melewati masa berlaku ({asesor.masa_berlaku_sertifikat}). Kartu tetap bisa diunduh
                                    namun diberi cap &ldquo;Tidak Berlaku&rdquo;. Perbarui tanggal di biodata setelah sertifikat diperpanjang.
                                </AlertDescription>
                            </Alert>
                        )}

                        <div className="bg-card max-w-3xl rounded-xl border shadow-xs">
                            <div className="bg-muted/40 flex justify-center rounded-t-xl p-6 md:p-10">
                                <KartuAsesorCanvas
                                    ref={kartuRef}
                                    asesor={asesor}
                                    lembaga={lembaga}
                                    diterbitkan={diterbitkan}
                                    className="h-auto w-full max-w-[540px] rounded-[18px] shadow-xl"
                                />
                            </div>

                            <div className="flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <p className="text-muted-foreground text-xs">PNG 1012 × 638 px, setara 300 dpi pada ukuran kartu ID standar.</p>

                                <div className="flex gap-2">
                                    <Button variant="outline" onClick={print}>
                                        <Printer />
                                        Cetak / PDF
                                    </Button>

                                    <Button onClick={download} disabled={downloading}>
                                        <Download />
                                        Unduh kartu
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </>
                )}
            </div>
        </AppLayout>
    );
}
