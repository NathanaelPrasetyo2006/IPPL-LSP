import { cn } from '@/lib/utils';
import { type StatusPeriode, type StatusPermohonan } from '@/types';
import { CalendarClock, CircleCheck, CircleX, Clock, DoorClosed, DoorOpen, type LucideIcon } from 'lucide-react';

interface BadgeMeta {
    label: string;
    icon: LucideIcon;
    className: string;
}

const NADA = {
    amber: 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-950/50 dark:text-amber-400 dark:ring-amber-500/30',
    emerald: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-950/50 dark:text-emerald-400 dark:ring-emerald-500/30',
    red: 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-950/50 dark:text-red-400 dark:ring-red-500/30',
    sky: 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-950/50 dark:text-sky-400 dark:ring-sky-500/30',
    slate: 'bg-slate-100 text-slate-600 ring-slate-500/20 dark:bg-slate-800/60 dark:text-slate-300 dark:ring-slate-500/30',
};

export const STATUS_PERMOHONAN: Record<StatusPermohonan, BadgeMeta> = {
    diajukan: { label: 'Menunggu verifikasi', icon: Clock, className: NADA.amber },
    diterima: { label: 'Diterima', icon: CircleCheck, className: NADA.emerald },
    ditolak: { label: 'Ditolak', icon: CircleX, className: NADA.red },
};

export const STATUS_PERIODE: Record<StatusPeriode, BadgeMeta> = {
    dibuka: { label: 'Dibuka', icon: DoorOpen, className: NADA.emerald },
    dijadwalkan: { label: 'Dijadwalkan', icon: CalendarClock, className: NADA.sky },
    ditutup: { label: 'Ditutup', icon: DoorClosed, className: NADA.slate },
};

function Badge({ meta }: { meta: BadgeMeta }) {
    const Icon = meta.icon;

    return (
        <span
            className={cn(
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset',
                meta.className,
            )}
        >
            <Icon className="size-3" />
            {meta.label}
        </span>
    );
}

export function StatusPermohonanBadge({ status }: { status: StatusPermohonan }) {
    return <Badge meta={STATUS_PERMOHONAN[status]} />;
}

export function StatusPeriodeBadge({ status }: { status: StatusPeriode }) {
    return <Badge meta={STATUS_PERIODE[status]} />;
}
