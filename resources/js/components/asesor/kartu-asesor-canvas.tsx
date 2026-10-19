import { type KartuAsesor } from '@/types';
import QRCode from 'qrcode';
import { forwardRef, useEffect, useImperativeHandle, useRef, useState } from 'react';

/** Ukuran kartu ID standar CR80 (85,6 × 54 mm) pada resolusi cetak ±300 dpi. */
export const KARTU_WIDTH = 1012;
export const KARTU_HEIGHT = 638;

const FONT = '"Instrument Sans", ui-sans-serif, system-ui, sans-serif';

const WARNA = {
    primer: '#1e3a8a',
    primerTerang: '#2563eb',
    aksen: '#f59e0b',
    teks: '#0f172a',
    teksRedup: '#64748b',
    garis: '#e2e8f0',
    bahaya: '#dc2626',
};

/** Path ikon logo aplikasi (sama dengan AppLogoIcon), viewBox 40 × 42. */
const LOGO_PATH =
    'M17.2 5.63325L8.6 0.855469L0 5.63325V32.1434L16.2 41.1434L32.4 32.1434V23.699L40 19.4767V9.85547L31.4 5.07769L22.8 9.85547V18.2999L17.2 21.411V5.63325ZM38 18.2999L32.4 21.411V15.2545L38 12.1434V18.2999ZM36.9409 10.4439L31.4 13.5221L25.8591 10.4439L31.4 7.36561L36.9409 10.4439ZM24.8 18.2999V12.1434L30.4 15.2545V21.411L24.8 18.2999ZM23.8 20.0323L29.3409 23.1105L16.2 30.411L10.6591 27.3328L23.8 20.0323ZM7.6 27.9212L15.2 32.1434V38.2999L2 30.9666V7.92116L7.6 11.0323V27.9212ZM8.6 9.29991L3.05913 6.22165L8.6 3.14339L14.1409 6.22165L8.6 9.29991ZM30.4 24.8101L17.2 32.1434V38.2999L30.4 30.9666V24.8101ZM9.6 11.0323L15.2 7.92117V22.5221L9.6 25.6333V11.0323Z';

export interface KartuAsesorCanvasHandle {
    /** Gambar kartu sebagai PNG, atau null bila canvas belum siap. */
    toBlob: () => Promise<Blob | null>;
    toDataUrl: () => string | null;
}

interface KartuAsesorCanvasProps {
    asesor: KartuAsesor;
    lembaga: string;
    diterbitkan: string;
    className?: string;
}

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const image = new Image();
        image.onload = () => resolve(image);
        image.onerror = reject;
        image.src = src;
    });
}

function roundedRect(ctx: CanvasRenderingContext2D, x: number, y: number, w: number, h: number, r: number) {
    ctx.beginPath();
    ctx.roundRect(x, y, w, h, r);
}

/**
 * Wrap text: teks turun ke baris berikutnya selebar maxWidth, tanpa batas baris dan tanpa dipotong.
 * Kata yang lebih panjang dari satu baris (mis. nomor tanpa spasi) dipecah per huruf.
 */
function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string[] {
    const lines: string[] = [];
    let current = '';

    for (const word of text.split(/\s+/).filter(Boolean)) {
        const candidate = current ? `${current} ${word}` : word;

        if (ctx.measureText(candidate).width <= maxWidth) {
            current = candidate;
            continue;
        }

        if (current) {
            lines.push(current);
        }

        current = '';

        for (const huruf of word) {
            if (current && ctx.measureText(current + huruf).width > maxWidth) {
                lines.push(current);
                current = huruf;
            } else {
                current += huruf;
            }
        }
    }

    if (current) {
        lines.push(current);
    }

    return lines.length > 0 ? lines : ['-'];
}

/**
 * QR berisi kode asesor di atas pelat putih, dengan zona tenang 2 modul supaya mudah dipindai.
 * Modul digambar sebagai kotak biasa sehingga tetap tajam saat kartu dicetak.
 */
function drawQr(ctx: CanvasRenderingContext2D, text: string, x: number, y: number, size: number) {
    const { modules } = QRCode.create(text, { errorCorrectionLevel: 'M' });
    const tepi = 2;
    const modul = Math.floor(size / (modules.size + tepi * 2));
    const offset = (size - modul * modules.size) / 2;

    ctx.fillStyle = '#ffffff';
    roundedRect(ctx, x, y, size, size, 12);
    ctx.fill();

    ctx.fillStyle = WARNA.teks;

    for (let row = 0; row < modules.size; row++) {
        for (let col = 0; col < modules.size; col++) {
            if (modules.get(row, col)) {
                ctx.fillRect(x + offset + col * modul, y + offset + row * modul, modul, modul);
            }
        }
    }
}

/** Gambar foto mengisi kotak tanpa distorsi (seperti object-fit: cover). */
function drawCover(ctx: CanvasRenderingContext2D, image: HTMLImageElement, x: number, y: number, w: number, h: number) {
    const scale = Math.max(w / image.width, h / image.height);
    const sw = w / scale;
    const sh = h / scale;

    ctx.drawImage(image, (image.width - sw) / 2, (image.height - sh) / 2, sw, sh, x, y, w, h);
}

async function drawKartu(canvas: HTMLCanvasElement, { asesor, lembaga, diterbitkan }: KartuAsesorCanvasProps) {
    const ctx = canvas.getContext('2d');

    if (!ctx) {
        return;
    }

    // Tunggu font web termuat supaya teks tidak digambar dengan font cadangan.
    await document.fonts.ready;

    const foto = asesor.foto_url ? await loadImage(asesor.foto_url).catch(() => null) : null;

    const W = KARTU_WIDTH;
    const H = KARTU_HEIGHT;

    ctx.clearRect(0, 0, W, H);

    // Latar kartu.
    roundedRect(ctx, 0, 0, W, H, 36);
    ctx.save();
    ctx.clip();

    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    // Ornamen lingkaran samar di kanan bawah.
    ctx.fillStyle = 'rgba(37, 99, 235, 0.06)';
    ctx.beginPath();
    ctx.arc(W - 40, H - 30, 260, 0, Math.PI * 2);
    ctx.fill();
    ctx.beginPath();
    ctx.arc(W - 40, H - 30, 170, 0, Math.PI * 2);
    ctx.fill();

    // Header.
    const header = ctx.createLinearGradient(0, 0, W, 0);
    header.addColorStop(0, WARNA.primer);
    header.addColorStop(1, WARNA.primerTerang);
    ctx.fillStyle = header;
    ctx.fillRect(0, 0, W, 150);

    ctx.fillStyle = WARNA.aksen;
    ctx.fillRect(0, 150, W, 8);

    // Logo.
    ctx.save();
    ctx.translate(48, 34);
    ctx.fillStyle = 'rgba(255, 255, 255, 0.15)';
    roundedRect(ctx, 0, 0, 84, 84, 18);
    ctx.fill();
    ctx.translate(18, 16);
    ctx.scale(1.2, 1.2);
    ctx.fillStyle = '#ffffff';
    ctx.fill(new Path2D(LOGO_PATH), 'evenodd');
    ctx.restore();

    ctx.textBaseline = 'alphabetic';
    ctx.fillStyle = '#ffffff';
    // Nama lembaga yang panjang diperkecil hurufnya, tidak dipotong.
    let ukuranLembaga = 42;
    ctx.font = `600 ${ukuranLembaga}px ${FONT}`;

    while (ctx.measureText(lembaga.toUpperCase()).width > W - 220 && ukuranLembaga > 20) {
        ukuranLembaga -= 1;
        ctx.font = `600 ${ukuranLembaga}px ${FONT}`;
    }

    ctx.fillText(lembaga.toUpperCase(), 156, 80);

    ctx.fillStyle = 'rgba(255, 255, 255, 0.85)';
    ctx.font = `500 24px ${FONT}`;
    ctx.fillText('LEMBAGA SERTIFIKASI PROFESI', 156, 116);

    // Judul kartu.
    ctx.fillStyle = WARNA.primer;
    ctx.font = `600 26px ${FONT}`;
    ctx.letterSpacing = '6px';
    ctx.fillText('KARTU TANDA ASESOR', 330, 212);
    ctx.letterSpacing = '0px';

    // Pas foto 3 × 4.
    const fx = 56;
    const fy = 196;
    const fw = 228;
    const fh = 304;

    ctx.save();
    ctx.shadowColor = 'rgba(15, 23, 42, 0.18)';
    ctx.shadowBlur = 18;
    ctx.shadowOffsetY = 6;
    ctx.fillStyle = '#ffffff';
    roundedRect(ctx, fx - 8, fy - 8, fw + 16, fh + 16, 20);
    ctx.fill();
    ctx.restore();

    ctx.save();
    roundedRect(ctx, fx, fy, fw, fh, 14);
    ctx.clip();
    ctx.fillStyle = '#f1f5f9';
    ctx.fillRect(fx, fy, fw, fh);

    if (foto) {
        drawCover(ctx, foto, fx, fy, fw, fh);
    } else {
        ctx.fillStyle = WARNA.teksRedup;
        ctx.font = `500 22px ${FONT}`;
        ctx.textAlign = 'center';
        ctx.fillText('Pas foto', fx + fw / 2, fy + fh / 2);
        ctx.textAlign = 'left';
    }

    // Pita "tidak berlaku" di atas bagian bawah foto bila sertifikat asesor sudah lewat masa berlakunya.
    if (asesor.kedaluwarsa) {
        ctx.fillStyle = 'rgba(220, 38, 38, 0.92)';
        ctx.fillRect(fx, fy + fh - 54, fw, 54);
        ctx.fillStyle = '#ffffff';
        ctx.font = `600 24px ${FONT}`;
        ctx.textAlign = 'center';
        ctx.fillText('TIDAK BERLAKU', fx + fw / 2, fy + fh - 19);
        ctx.textAlign = 'left';
    }
    ctx.restore();

    // Kode asesor di bawah foto, seperti nomor pada kartu identitas.
    if (asesor.kode_asesor) {
        ctx.fillStyle = WARNA.teks;
        ctx.font = `600 24px ${FONT}`;
        ctx.letterSpacing = '2px';
        ctx.textAlign = 'center';
        ctx.fillText(asesor.kode_asesor, fx + fw / 2, fy + fh + 44);
        ctx.textAlign = 'left';
        ctx.letterSpacing = '0px';
    }

    // QR kode asesor di pojok kanan bawah, tepat di atas footer.
    const qrSize = 152;
    const qrX = W - 56 - qrSize;
    const qrY = H - 76 - 16 - qrSize;

    if (asesor.kode_asesor) {
        drawQr(ctx, asesor.kode_asesor, qrX, qrY, qrSize);
    }

    // Data asesor.
    const tx = 330;
    const maxNama = W - tx - 56;
    // Baris data sejajar dengan QR, jadi lebarnya berhenti sebelum QR.
    const maxBaris = qrX - 24 - tx;
    // Baris terakhir harus berakhir di atas garis footer.
    const batasBawah = H - 76 - 14;

    const dataBaris = [
        { label: 'No. Registrasi / MET', nilai: asesor.no_reg, warna: WARNA.teks },
        { label: 'Bidang Keahlian', nilai: asesor.bidang_keahlian ?? '-', warna: WARNA.teks },
        { label: 'Berlaku s.d.', nilai: asesor.masa_berlaku_sertifikat ?? '-', warna: asesor.kedaluwarsa ? WARNA.bahaya : WARNA.teks },
    ];

    // Teks panjang turun ke baris berikutnya, tidak dipotong. Bila semuanya tidak muat di atas footer,
    // ukuran huruf seluruh blok diperkecil bertahap sampai muat.
    const susun = (skala: number) => {
        ctx.font = `600 ${Math.round(40 * skala)}px ${FONT}`;
        let nama = wrapText(ctx, asesor.nama, maxNama);

        // Nama yang sangat panjang bisa turun sampai sejajar QR; saat itu lebarnya ikut dibatasi.
        if (268 + (nama.length - 1) * 46 * skala > qrY - 8) {
            nama = wrapText(ctx, asesor.nama, maxBaris);
        }

        ctx.font = `600 ${Math.round(27 * skala)}px ${FONT}`;
        const isi = dataBaris.map((baris) => wrapText(ctx, baris.nilai, maxBaris));

        let y = 268 + nama.length * 46 * skala;
        let akhir = y;

        const posisi = isi.map((lines) => {
            const label = y;
            const nilai = y + 34 * skala;

            akhir = nilai + (lines.length - 1) * 32 * skala;
            y = akhir + 38 * skala;

            return { label, nilai };
        });

        return { skala, nama, isi, posisi, muat: akhir <= batasBawah };
    };

    let tata = susun(1);

    for (let skala = 0.95; !tata.muat && skala >= 0.6; skala -= 0.05) {
        tata = susun(skala);
    }

    ctx.fillStyle = WARNA.teks;
    ctx.font = `600 ${Math.round(40 * tata.skala)}px ${FONT}`;
    tata.nama.forEach((line, i) => ctx.fillText(line, tx, 268 + i * 46 * tata.skala));

    dataBaris.forEach((baris, i) => {
        ctx.fillStyle = WARNA.teksRedup;
        ctx.font = `500 ${Math.round(19 * tata.skala)}px ${FONT}`;
        ctx.fillText(baris.label.toUpperCase(), tx, tata.posisi[i].label);

        ctx.fillStyle = baris.warna;
        ctx.font = `600 ${Math.round(27 * tata.skala)}px ${FONT}`;
        tata.isi[i].forEach((line, j) => ctx.fillText(line, tx, tata.posisi[i].nilai + j * 32 * tata.skala));
    });

    // Footer.
    ctx.fillStyle = WARNA.garis;
    ctx.fillRect(0, H - 76, W, 2);

    ctx.fillStyle = WARNA.teksRedup;
    ctx.font = `500 19px ${FONT}`;
    ctx.fillText(`Diterbitkan ${diterbitkan}`, 56, H - 32);

    // Instansi panjang turun ke baris berikutnya; hurufnya diperkecil bila lebih dari dua baris.
    if (asesor.instansi) {
        let ukuran = 19;
        let lines = wrapText(ctx, asesor.instansi, 440);

        while (lines.length > 2 && ukuran > 14) {
            ukuran -= 1;
            ctx.font = `500 ${ukuran}px ${FONT}`;
            lines = wrapText(ctx, asesor.instansi, 440);
        }

        // Baris-baris instansi berpusat pada garis teks "Diterbitkan".
        const tinggiBaris = ukuran * 1.25;
        const awal = H - 32 - ((lines.length - 1) * tinggiBaris) / 2;

        ctx.textAlign = 'right';
        lines.forEach((line, i) => ctx.fillText(line, W - 56, awal + i * tinggiBaris));
        ctx.textAlign = 'left';
    }

    ctx.restore();
}

/**
 * Kartu Tanda Asesor yang digambar di <canvas>, sehingga bisa langsung diunduh
 * sebagai PNG resolusi cetak tanpa proses di server.
 */
const KartuAsesorCanvas = forwardRef<KartuAsesorCanvasHandle, KartuAsesorCanvasProps>(function KartuAsesorCanvas(props, ref) {
    const canvasRef = useRef<HTMLCanvasElement>(null);
    const [ready, setReady] = useState(false);

    useImperativeHandle(ref, () => ({
        toBlob: () =>
            new Promise((resolve) => {
                if (!canvasRef.current || !ready) {
                    resolve(null);
                    return;
                }

                canvasRef.current.toBlob(resolve, 'image/png');
            }),
        toDataUrl: () => (canvasRef.current && ready ? canvasRef.current.toDataURL('image/png') : null),
    }));

    const { asesor, lembaga, diterbitkan } = props;

    useEffect(() => {
        let cancelled = false;

        if (canvasRef.current) {
            setReady(false);
            drawKartu(canvasRef.current, { asesor, lembaga, diterbitkan }).then(() => !cancelled && setReady(true));
        }

        return () => {
            cancelled = true;
        };
    }, [asesor, lembaga, diterbitkan]);

    return (
        <canvas
            ref={canvasRef}
            width={KARTU_WIDTH}
            height={KARTU_HEIGHT}
            className={props.className}
            role="img"
            aria-label={`Kartu Tanda Asesor ${asesor.nama}, kode asesor ${asesor.kode_asesor ?? '-'}, no. registrasi ${asesor.no_reg}`}
        />
    );
});

export default KartuAsesorCanvas;
