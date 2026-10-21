import { cn } from '@/lib/utils';
import { HTMLAttributes } from 'react';

/**
 * Pesan error di bawah isian. Atribut data-input-error dipakai lib/arahkan-ke-error
 * untuk menggulir ke isian pertama yang salah, dan oleh CSS untuk memerahkan bingkai isiannya.
 */
export default function InputError({ message, className = '', ...props }: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p {...props} data-input-error="" role="alert" className={cn('text-sm text-red-600 dark:text-red-400', className)}>
            {message}
        </p>
    ) : null;
}
