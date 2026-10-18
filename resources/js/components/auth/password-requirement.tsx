import { cn } from '@/lib/utils';
import { Check, X } from 'lucide-react';

export default function PasswordRequirement({ met, label }: { met: boolean; label: string }) {
    return (
        <li className={cn('flex items-center gap-2', met ? 'text-emerald-600 dark:text-emerald-400' : 'text-muted-foreground')}>
            {met ? <Check className="size-3.5 shrink-0" /> : <X className="size-3.5 shrink-0" />}
            {label}
        </li>
    );
}
