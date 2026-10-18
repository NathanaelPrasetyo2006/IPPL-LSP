import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { type NotifikasiItem, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Bell, CheckCheck, CircleCheck, CircleX, Info } from 'lucide-react';

const IKON: Record<NotifikasiItem['tipe'], { icon: typeof Info; className: string }> = {
    info: { icon: Info, className: 'text-sky-600 bg-sky-50 dark:bg-sky-950/50 dark:text-sky-400' },
    sukses: { icon: CircleCheck, className: 'text-emerald-600 bg-emerald-50 dark:bg-emerald-950/50 dark:text-emerald-400' },
    gagal: { icon: CircleX, className: 'text-red-600 bg-red-50 dark:bg-red-950/50 dark:text-red-400' },
};

/**
 * Lonceng notifikasi di header: hasil verifikasi untuk asesi, permohonan baru untuk admin.
 */
export function NotifikasiBell() {
    const { notifikasi } = usePage<SharedData>().props;

    if (!notifikasi) {
        return null;
    }

    const { belum_dibaca: belumDibaca, terbaru } = notifikasi;

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon" className="relative size-9" aria-label={`Notifikasi, ${belumDibaca} belum dibaca`}>
                    <Bell className="size-5" />
                    {belumDibaca > 0 && (
                        <span className="absolute top-1 right-1 flex min-w-4 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] leading-4 font-semibold text-white tabular-nums">
                            {belumDibaca > 9 ? '9+' : belumDibaca}
                        </span>
                    )}
                </Button>
            </DropdownMenuTrigger>

            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between px-3 py-2.5">
                    <DropdownMenuLabel className="p-0 text-sm">Notifikasi</DropdownMenuLabel>

                    {belumDibaca > 0 && (
                        <button
                            type="button"
                            onClick={() => router.post(route('notifikasi.baca-semua'), {}, { preserveScroll: true })}
                            className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-xs"
                        >
                            <CheckCheck className="size-3.5" />
                            Tandai semua dibaca
                        </button>
                    )}
                </div>

                <DropdownMenuSeparator className="m-0" />

                {terbaru.length === 0 ? (
                    <p className="text-muted-foreground px-3 py-8 text-center text-sm">Belum ada notifikasi.</p>
                ) : (
                    <div className="max-h-96 overflow-y-auto">
                        {terbaru.map((item) => {
                            const { icon: Icon, className } = IKON[item.tipe] ?? IKON.info;

                            return (
                                <DropdownMenuItem
                                    key={item.id}
                                    onSelect={() => router.post(route('notifikasi.baca', item.id))}
                                    className={cn(
                                        'flex cursor-pointer items-start gap-3 rounded-none px-3 py-3',
                                        !item.dibaca && 'bg-sky-50/60 dark:bg-sky-950/20',
                                    )}
                                >
                                    <span className={cn('mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full', className)}>
                                        <Icon className="size-4" />
                                    </span>

                                    <span className="min-w-0 flex-1">
                                        <span className="flex items-center gap-2">
                                            <span className="truncate text-sm font-medium">{item.judul}</span>
                                            {!item.dibaca && <span className="size-2 shrink-0 rounded-full bg-sky-500" aria-label="Belum dibaca" />}
                                        </span>
                                        <span className="text-muted-foreground mt-0.5 line-clamp-2 block text-xs">{item.pesan}</span>
                                        {item.waktu && <span className="text-muted-foreground/80 mt-1 block text-[11px]">{item.waktu}</span>}
                                    </span>
                                </DropdownMenuItem>
                            );
                        })}
                    </div>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
