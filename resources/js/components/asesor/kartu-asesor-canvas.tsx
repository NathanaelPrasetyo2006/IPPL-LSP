import { type KartuAsesor } from '@/types';
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

/** Pecah teks menjadi beberapa baris agar muat di lebar tertentu; baris terakhir dipotong dengan elipsis. */
function wrapText(ctx: CanvasRenderingContext2D, text: string, maxWidth: number, maxLines: number): string[] {
    const words = text.split(/\s+/).filter(Boolean);
    const lines: string[] = [];
    let current = '';

    for (const word of words) {
        const candidate = current ? `${current} ${word}` : word;

        if (ctx.measureText(candidate).width <= maxWidth || !current) {
            current = candidate;
        } else {
            lines.push(current);
            current = word;
        }
    }

    if (current) {
        lines.push(current);
    }

    if (lines.length <= maxLines) {
        return lines;
    }

    const visible = lines.slice(0, maxLines);
    let last = `${visible[maxLines - 1]} ${lines.slice(maxLines).join(' ')}`;

    while (ctx.measureText(`${last}…`).width > maxWidth && last.length > 0) {
        last = last.slice(0, -1);
    }

    visible[maxLines - 1] = `${last.trimEnd()}…`;

    return visible;
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
    ctx.font = `600 42px ${FONT}`;
    ctx.fillText(wrapText(ctx, lembaga.toUpperCase(), W - 220, 1)[0] ?? '', 156, 80);

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

    // Pita "tidak berlaku" di bawah foto bila sertifikat asesor sudah lewat masa berlakunya.
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

    // Data asesor.
    const tx = 330;
    const maxText = W - tx - 56;

    ctx.fillStyle = WARNA.teks;
    ctx.font = `600 40px ${FONT}`;
    const namaLines = wrapText(ctx, asesor.nama, maxText, 2);
    namaLines.forEach((line, i) => ctx.fillText(line, tx, 268 + i * 46));

    // Nama dua baris menggeser baris data ke bawah; ruang di atas footer sudah diperhitungkan.
    let y = 268 + namaLines.length * 46;

    const baris = (label: string, value: string, warna: string = WARNA.teks) => {
        ctx.fillStyle = WARNA.teksRedup;
        ctx.font = `500 19px ${FONT}`;
        ctx.fillText(label.toUpperCase(), tx, y);

        ctx.fillStyle = warna;
        ctx.font = `600 27px ${FONT}`;
        ctx.fillText(wrapText(ctx, value, maxText, 1)[0] ?? '-', tx, y + 34);

        y += 72;
    };

    baris('No. Registrasi / MET', asesor.no_reg);
    baris('Bidang Keahlian', asesor.bidang_keahlian ?? '-');
    baris('Berlaku s.d.', asesor.masa_berlaku_sertifikat ?? '-', asesor.kedaluwarsa ? WARNA.bahaya : WARNA.teks);

    // Footer.
    ctx.fillStyle = WARNA.garis;
    ctx.fillRect(0, H - 76, W, 2);

    ctx.fillStyle = WARNA.teksRedup;
    ctx.font = `500 19px ${FONT}`;
    ctx.fillText(`Diterbitkan ${diterbitkan}`, 56, H - 32);

    ctx.textAlign = 'right';
    ctx.fillText(wrapText(ctx, asesor.instansi ?? '', 440, 1)[0] ?? '', W - 56, H - 32);
    ctx.textAlign = 'left';

    ctx.restore();
}

/**
 * Kartu Tanda Asesor yang digambar di <canvas>, sehingga bisa langsung diunduh
 * sebagai PNG resolusi cetak tanpa library tambahan di server maupun browser.
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
            aria-label={`Kartu Tanda Asesor ${asesor.nama}, no. registrasi ${asesor.no_reg}`}
        />
    );
});

export default KartuAsesorCanvas;
