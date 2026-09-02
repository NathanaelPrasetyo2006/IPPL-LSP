import { cn } from '@/lib/utils';
import { type LucideIcon } from 'lucide-react';

interface StatCardProps {
    icon: LucideIcon;
    /** Kelas warna latar ikon, mis. "bg-blue-600". */
    tone: string;
    value: number;
    label: string;
}

export default function StatCard({ icon: Icon, tone, value, label }: StatCardProps) {
    return (
        <div className="bg-card rounded-xl border p-5 shadow-xs">
            <div className={cn('mb-5 flex size-11 items-center justify-center rounded-xl text-white', tone)}>
                <Icon className="size-5" />
            </div>

            <p className="text-3xl font-bold tracking-tight tabular-nums">{value}</p>
            <p className="text-muted-foreground mt-1 text-sm">{label}</p>
        </div>
    );
}
