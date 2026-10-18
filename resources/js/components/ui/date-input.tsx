import * as React from 'react';

import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/** Tombol yang tetap berfungsi normal: pindah kolom dan menutup kalender. */
const TOMBOL_NAVIGASI = ['Tab', 'Escape'];

/** Tombol keyboard yang membuka kalender (pengganti klik bagi pengguna keyboard). */
const TOMBOL_BUKA = ['Enter', ' ', 'ArrowDown'];

function bukaKalender(input: HTMLInputElement) {
    try {
        input.showPicker();
    } catch {
        // Browser lama tanpa showPicker() tetap bisa memakai ikon kalender bawaan.
    }
}

/**
 * Input tanggal yang hanya diisi lewat kalender: klik di mana saja membuka kalender,
 * sedangkan ketik manual dan paste diblokir agar format tanggal selalu valid.
 */
const DateInput = React.forwardRef<HTMLInputElement, Omit<React.ComponentProps<'input'>, 'type'>>(
    ({ className, onClick, onKeyDown, onPaste, ...props }, ref) => (
        <Input
            ref={ref}
            type="date"
            className={cn('cursor-pointer caret-transparent [&::-webkit-calendar-picker-indicator]:cursor-pointer', className)}
            onClick={(e) => {
                bukaKalender(e.currentTarget);
                onClick?.(e);
            }}
            onKeyDown={(e) => {
                if (!TOMBOL_NAVIGASI.includes(e.key)) {
                    e.preventDefault();

                    if (TOMBOL_BUKA.includes(e.key)) {
                        bukaKalender(e.currentTarget);
                    }
                }

                onKeyDown?.(e);
            }}
            onPaste={(e) => {
                e.preventDefault();
                onPaste?.(e);
            }}
            {...props}
        />
    ),
);

DateInput.displayName = 'DateInput';

export { DateInput };
