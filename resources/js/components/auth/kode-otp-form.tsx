import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type OtpStatus, type SharedData } from '@/types';
import { router, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, MailCheck, RotateCw } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const PANJANG = 6;

const lengkap = (kode: string) => /^\d{6}$/.test(kode);

/** Hitung mundur detik; mulai ulang setiap server mengirim status baru. */
function useHitungMundur(detik: number, pemicu: unknown): number {
    const [sisa, setSisa] = useState(detik);

    useEffect(() => {
        setSisa(detik);

        if (detik <= 0) {
            return;
        }

        const interval = setInterval(() => setSisa((current) => Math.max(0, current - 1)), 1000);

        return () => clearInterval(interval);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pemicu]);

    return sisa;
}

function formatDetik(detik: number): string {
    return `${Math.floor(detik / 60)}:${String(detik % 60).padStart(2, '0')}`;
}

/** Enam kotak angka; mendukung tempel (paste) kode utuh dan backspace ke kotak sebelumnya. */
function OtpInput({
    value,
    onChange,
    disabled,
    invalid,
}: {
    value: string;
    onChange: (value: string) => void;
    disabled?: boolean;
    invalid?: boolean;
}) {
    const refs = useRef<(HTMLInputElement | null)[]>([]);

    const isi = (mulai: number, teks: string) => {
        const angka = teks.replace(/\D/g, '');

        if (!angka) {
            return;
        }

        const digit = value.padEnd(PANJANG, ' ').split('');
        angka
            .slice(0, PANJANG - mulai)
            .split('')
            .forEach((karakter, i) => (digit[mulai + i] = karakter));

        onChange(digit.join('').replace(/\s+$/, ''));
        refs.current[Math.min(mulai + angka.length, PANJANG - 1)]?.focus();
    };

    return (
        <div className="flex justify-center gap-2" role="group" aria-label="Kode verifikasi 6 digit">
            {Array.from({ length: PANJANG }, (_, i) => (
                <input
                    key={i}
                    ref={(el) => {
                        refs.current[i] = el;
                    }}
                    value={value[i]?.trim() ?? ''}
                    inputMode="numeric"
                    autoComplete={i === 0 ? 'one-time-code' : 'off'}
                    autoFocus={i === 0}
                    maxLength={PANJANG}
                    disabled={disabled}
                    aria-label={`Digit ${i + 1}`}
                    aria-invalid={invalid}
                    onFocus={(e) => e.target.select()}
                    onPaste={(e) => {
                        e.preventDefault();
                        isi(i, e.clipboardData.getData('text'));
                    }}
                    onChange={(e) => isi(i, e.target.value.slice(-1))}
                    onKeyDown={(e) => {
                        if (e.key === 'Backspace') {
                            e.preventDefault();
                            const digit = value.padEnd(PANJANG, ' ').split('');
                            const target = digit[i]?.trim() ? i : Math.max(0, i - 1);
                            digit[target] = ' ';
                            onChange(digit.join('').replace(/\s+$/, ''));
                            refs.current[target]?.focus();
                        } else if (e.key === 'ArrowLeft') {
                            refs.current[i - 1]?.focus();
                        } else if (e.key === 'ArrowRight') {
                            refs.current[i + 1]?.focus();
                        }
                    }}
                    className={cn(
                        'border-input bg-background focus-visible:ring-ring size-11 rounded-md border text-center font-mono text-lg font-semibold focus-visible:ring-2 focus-visible:outline-hidden disabled:opacity-50 sm:size-12',
                        invalid && 'border-destructive',
                    )}
                />
            ))}
        </div>
    );
}

interface KodeOtpFormProps {
    verifikasiUrl: string;
    kirimUlangUrl: string;
    otp: OtpStatus;
    submitLabel?: string;
}

/**
 * Isian kode OTP: diperiksa otomatis begitu 6 digit terisi. Bila salah, kode tidak dihapus
 * supaya pengguna cukup membetulkan digit yang keliru.
 */
export default function KodeOtpForm({ verifikasiUrl, kirimUlangUrl, otp, submitLabel = 'Verifikasi' }: KodeOtpFormProps) {
    const { errors, flash } = usePage<SharedData>().props;
    const { data, setData, post, processing } = useForm({ kode: '' });
    const [mengirim, setMengirim] = useState(false);
    const terakhirDikirim = useRef('');

    const kedaluwarsa = useHitungMundur(otp.kedaluwarsa_dalam, otp);
    const kirimUlang = useHitungMundur(otp.kirim_ulang_dalam, otp);

    const periksa = () => {
        terakhirDikirim.current = data.kode;
        post(verifikasiUrl, { preserveScroll: true });
    };

    // Kode yang sama tidak dikirim ulang otomatis; pengguna perlu mengubah digit dulu.
    useEffect(() => {
        if (lengkap(data.kode) && !processing && data.kode !== terakhirDikirim.current) {
            periksa();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [data.kode]);

    const kirimKodeBaru = () => {
        router.post(
            kirimUlangUrl,
            {},
            {
                preserveScroll: true,
                onStart: () => setMengirim(true),
                onSuccess: () => {
                    terakhirDikirim.current = '';
                    setData('kode', '');
                },
                onFinish: () => setMengirim(false),
            },
        );
    };

    return (
        <form
            className="flex flex-col gap-5"
            onSubmit={(e) => {
                e.preventDefault();
                periksa();
            }}
        >
            {flash.status && (
                <div className="flex items-start gap-3 rounded-lg border border-emerald-500/40 bg-emerald-50 p-3 dark:bg-emerald-950/40">
                    <MailCheck className="mt-0.5 size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                    <p className="text-sm text-emerald-700 dark:text-emerald-400">{flash.status}</p>
                </div>
            )}

            <div className="grid gap-2">
                <OtpInput value={data.kode} onChange={(value) => setData('kode', value)} disabled={processing} invalid={!!errors.kode} />
                <InputError message={errors.kode} className="text-center" />

                <p className={cn('text-center text-xs', kedaluwarsa > 0 ? 'text-muted-foreground' : 'text-amber-700 dark:text-amber-500')}>
                    {kedaluwarsa > 0 ? `Kode berlaku ${formatDetik(kedaluwarsa)} lagi.` : 'Kode sudah tidak berlaku. Minta kode baru.'}
                </p>
            </div>

            <Button type="submit" className="w-full" disabled={processing || !lengkap(data.kode)}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                {submitLabel}
            </Button>

            <div className="text-muted-foreground text-center text-sm">
                Tidak menerima kode?{' '}
                {otp.sisa_kirim_ulang <= 0 ? (
                    <span>Batas kirim ulang hari ini sudah habis.</span>
                ) : kirimUlang > 0 ? (
                    <span>Kirim ulang dalam {formatDetik(kirimUlang)}</span>
                ) : (
                    <button
                        type="button"
                        onClick={kirimKodeBaru}
                        disabled={mengirim}
                        className="text-foreground inline-flex items-center gap-1 font-medium underline-offset-4 hover:underline disabled:opacity-50"
                    >
                        <RotateCw className={cn('size-3.5', mengirim && 'animate-spin')} />
                        Kirim ulang kode
                    </button>
                )}
                <p className="mt-1 text-xs">Sisa kirim ulang hari ini: {otp.sisa_kirim_ulang} kali</p>
            </div>
        </form>
    );
}
