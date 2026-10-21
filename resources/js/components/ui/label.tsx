import * as LabelPrimitive from '@radix-ui/react-label';
import { cva, type VariantProps } from 'class-variance-authority';
import * as React from 'react';

import { cn } from '@/lib/utils';

const labelVariants = cva('text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70');

/** Bintang merah penanda isian wajib; dipakai seragam di semua form. */
export function TandaWajib() {
    return (
        <span aria-hidden="true" className="text-red-600 dark:text-red-400">
            {' '}
            *
        </span>
    );
}

const Label = React.forwardRef<
    React.ElementRef<typeof LabelPrimitive.Root>,
    React.ComponentPropsWithoutRef<typeof LabelPrimitive.Root> &
        VariantProps<typeof labelVariants> & {
            /** Isian wajib diberi bintang merah; isian opsional tidak. */
            required?: boolean;
        }
>(({ className, required, children, ...props }, ref) => (
    <LabelPrimitive.Root ref={ref} className={cn(labelVariants(), className)} {...props}>
        {children}
        {required && <TandaWajib />}
    </LabelPrimitive.Root>
));
Label.displayName = LabelPrimitive.Root.displayName;

export { Label };
