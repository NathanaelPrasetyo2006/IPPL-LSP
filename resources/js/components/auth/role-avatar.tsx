import { cn } from '@/lib/utils';
import { type UserRole } from '@/types';

const SKIN = '#FCE3C3';
const SKIN_SHADE = '#F2CDA4';

/**
 * Bagian yang sama untuk semua avatar: leher, telinga, kepala, dan headset.
 * Rambut & pakaian digambar per peran.
 */
function Head({ hair, headset, headsetDark }: { hair: React.ReactNode; headset: string; headsetDark: string }) {
    return (
        <>
            <path d="M51 64h18v24c0 5-18 5-18 0Z" fill={SKIN_SHADE} />
            <ellipse cx="39" cy="52" rx="4" ry="6" fill={SKIN_SHADE} />
            <ellipse cx="81" cy="52" rx="4" ry="6" fill={SKIN_SHADE} />
            <ellipse cx="60" cy="49" rx="21" ry="24" fill={SKIN} />
            {hair}

            <path d="M35.5 52C35.5 20 84.5 20 84.5 52" fill="none" stroke={headset} strokeWidth="4.5" strokeLinecap="round" />
            <rect x="30.5" y="44" width="9" height="16" rx="4.5" fill={headset} />
            <rect x="80.5" y="44" width="9" height="16" rx="4.5" fill={headset} />
            <path d="M85 59c0 12-7 16-17 16" fill="none" stroke={headsetDark} strokeWidth="3" strokeLinecap="round" />
            <rect x="59" y="72" width="11" height="6.5" rx="3.25" fill={headsetDark} />
        </>
    );
}

function AdminFigure() {
    return (
        <>
            <Head
                headset="#8B5CF6"
                headsetDark="#6D28D9"
                hair={<path d="M39 46c-1-17 9-23 21-23s22 6 21 23c-3-7-9-11-17-11-9 0-18 3-25 11Z" fill="#1F2937" />}
            />
            {/* Jas hitam, kemeja putih, dasi */}
            <path d="M12 124c0-25 20-38 48-38s48 13 48 38Z" fill="#1E1E24" />
            <path d="M47 87 60 113l13-26c-4-1-8.5-1-13-1s-9 0-13 1Z" fill="#FFFFFF" />
            <path d="M56.5 89h7l-1.5 5 4 17-6 7-6-7 4-17Z" fill="#0B0B0F" />
            <path d="M47 87 60 117l-10-13 4-6-10-6Z" fill="#33333D" />
            <path d="M73 87 60 117l10-13-4-6 10-6Z" fill="#33333D" />
        </>
    );
}

function AsesorFigure() {
    return (
        <>
            <Head
                headset="#38BDF8"
                headsetDark="#0369A1"
                hair={<path d="M39 47c-2-18 9-25 22-25s23 8 20 25c-2-7-5-11-9-13-8 4-20 4-27 2-3 3-5 7-6 11Z" fill="#3F2A1D" />}
            />
            {/* Blazer biru, kemeja biru muda tanpa dasi */}
            <path d="M12 124c0-25 20-38 48-38s48 13 48 38Z" fill="#1D4ED8" />
            <path d="M47 87 60 113l13-26c-4-1-8.5-1-13-1s-9 0-13 1Z" fill="#DBEAFE" />
            <path d="M54 87 60 98l6-11Z" fill={SKIN_SHADE} />
            <path d="M47 87 60 117l-10-13 4-6-10-6Z" fill="#1E40AF" />
            <path d="M73 87 60 117l10-13-4-6 10-6Z" fill="#1E40AF" />

            {/* Clipboard penilaian */}
            <g transform="rotate(-8 88 104)">
                <rect x="75" y="88" width="26" height="32" rx="3.5" fill="#B45309" />
                <rect x="78.5" y="93" width="19" height="25" rx="1.5" fill="#FFFFFF" />
                <rect x="83" y="85.5" width="10" height="6" rx="2" fill="#94A3B8" />
                <path d="m81 100 2.5 2.5 4.5-5" fill="none" stroke="#16A34A" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
                <path d="M90 100.5h5M81 107h14M81 112h10" stroke="#CBD5E1" strokeWidth="2" strokeLinecap="round" />
            </g>
        </>
    );
}

function AsesiFigure() {
    return (
        <>
            <Head
                headset="#22C55E"
                headsetDark="#15803D"
                hair={
                    <>
                        <path d="M37 52c-4-22 9-31 24-31 16 0 27 10 22 31-3-8-7-13-13-16-8 6-20 8-30 6-2 3-3 6-3 10Z" fill="#F59E0B" />
                        <path d="M50 26c6-3 15-3 21 0-6 1-14 1-21 0Z" fill="#FCD34D" />
                    </>
                }
            />
            {/* Kemeja kasual dengan kerah dan ID card peserta */}
            <path d="M12 124c0-25 20-38 48-38s48 13 48 38Z" fill="#5EB8C0" />
            <path d="M52 87 60 99l8-12Z" fill={SKIN_SHADE} />
            <path d="M50 85 60 99l-15-6Z" fill="#8FD3D9" />
            <path d="M70 85 60 99l15-6Z" fill="#8FD3D9" />
            <path d="M60 99v25" stroke="#4BA3AB" strokeWidth="1.5" />

            <path d="M51 89 57 107M69 89 63 107" stroke="#F97316" strokeWidth="2.5" strokeLinecap="round" />
            <rect x="52" y="105" width="16" height="14" rx="2.5" fill="#FFFFFF" stroke="#E2E8F0" />
            <rect x="55" y="108.5" width="5" height="6" rx="1" fill="#FDBA74" />
            <path d="M62 110h3.5M62 113.5h3.5" stroke="#CBD5E1" strokeWidth="1.5" strokeLinecap="round" />
        </>
    );
}

const FIGURES: Record<UserRole, () => React.JSX.Element> = {
    admin: AdminFigure,
    asesor: AsesorFigure,
    asesi: AsesiFigure,
};

/**
 * Ilustrasi avatar per peran. Dirancang di dalam lingkaran: bahu terpotong
 * oleh tepi bawah, jadi letakkan di wadah `rounded-full overflow-hidden`.
 */
export default function RoleAvatar({ role, className }: { role: UserRole; className?: string }) {
    const Figure = FIGURES[role];

    return (
        <svg viewBox="8 10 104 104" xmlns="http://www.w3.org/2000/svg" className={cn('size-full', className)} aria-hidden="true">
            <Figure />
        </svg>
    );
}
