import { router } from '@inertiajs/react';

/** Elemen yang bisa difokuskan sebagai "isian" milik sebuah pesan error, urut dari yang paling tepat. */
const ISIAN = [
    'input:not([type=hidden]):not([disabled])',
    'textarea:not([disabled])',
    'select:not([disabled])',
    'button[role=combobox]:not([disabled])',
    'button[role=checkbox]:not([disabled])',
    'button[role=radio]:not([disabled])',
    'button:not([disabled])',
].join(', ');

/** Batas naik ke elemen induk saat mencari isian di sekitar pesan error. */
const MAKS_NAIK = 3;

const terlihat = (el: Element) => el.getClientRects().length > 0;

/** Isian terdekat dari pesan error; diutamakan yang letaknya sebelum pesan, yaitu isian yang dimaksud. */
function isianUntuk(pesan: HTMLElement): HTMLElement | null {
    let wadah = pesan.parentElement;

    for (let naik = 0; wadah && naik < MAKS_NAIK; naik++, wadah = wadah.parentElement) {
        const kandidat = Array.from(wadah.querySelectorAll<HTMLElement>(ISIAN)).filter(terlihat);
        const sebelumPesan = kandidat.filter((el) => el.compareDocumentPosition(pesan) & Node.DOCUMENT_POSITION_FOLLOWING);
        const isian = sebelumPesan[0] ?? kandidat[0];

        if (isian) {
            return isian;
        }
    }

    return null;
}

/**
 * Gulir ke pesan error pertama (urut tampilan) lalu fokuskan isiannya, supaya pengguna
 * tidak perlu mencari sendiri kolom yang salah di form yang panjang.
 */
export function arahkanKeError(): boolean {
    const pesan = Array.from(document.querySelectorAll<HTMLElement>('[data-input-error]')).find(terlihat);

    if (!pesan) {
        return false;
    }

    const isian = isianUntuk(pesan);

    // Isian diletakkan di tengah layar supaya label di atas dan pesan di bawahnya ikut terlihat.
    (isian ?? pesan).scrollIntoView({ behavior: 'smooth', block: 'center' });
    isian?.focus({ preventScroll: true });

    return true;
}

/**
 * Pasang sekali di app.tsx: setiap kiriman form Inertia yang dibalas error validasi
 * langsung diarahkan ke isian pertama yang salah.
 */
export function pasangArahkanKeError(): void {
    router.on('error', () => {
        // Pesan error baru muncul setelah React merender ulang; dicoba beberapa frame.
        let sisa = 10;
        const coba = () => {
            if (!arahkanKeError() && --sisa > 0) {
                requestAnimationFrame(coba);
            }
        };

        requestAnimationFrame(coba);
    });
}
