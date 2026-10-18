import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { type SkemaSertifikasi, type UnitKompetensi } from '@/types';
import { LayoutList, ListPlus, Pencil, Trash2 } from 'lucide-react';

interface SkemaDetailDialogProps {
    skema: SkemaSertifikasi | null;
    onOpenChange: (open: boolean) => void;
    /** Aksi kelola hanya tampil untuk admin. */
    canManage: boolean;
    onAddUnit: () => void;
    onEditUnit: (unit: UnitKompetensi) => void;
    onDeleteUnit: (unit: UnitKompetensi) => void;
}

export default function SkemaDetailDialog({ skema, onOpenChange, canManage, onAddUnit, onEditUnit, onDeleteUnit }: SkemaDetailDialogProps) {
    const units = skema?.unit_kompetensi ?? [];

    return (
        <Dialog open={skema !== null} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90svh] gap-0 overflow-hidden p-0 sm:max-w-3xl">
                <DialogHeader className="border-b p-6 pr-12">
                    <p className="text-muted-foreground text-xs font-medium tracking-wide uppercase">Detail Skema Sertifikasi</p>
                    <DialogTitle className="text-xl">{skema?.judul}</DialogTitle>
                    <DialogDescription className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span className="text-foreground font-mono text-xs">{skema?.nomor}</span>
                        <span className="inline-flex items-center gap-1.5">
                            <LayoutList className="size-3.5" />
                            {units.length} unit kompetensi
                        </span>
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-center justify-between gap-3 px-6 pt-5 pb-3">
                    <h3 className="text-sm font-semibold">Daftar Unit Kompetensi</h3>

                    {canManage && (
                        <Button size="sm" onClick={onAddUnit}>
                            <ListPlus />
                            Tambah Unit
                        </Button>
                    )}
                </div>

                <div className="max-h-[55svh] overflow-y-auto px-6 pb-6">
                    {units.length === 0 ? (
                        <div className="text-muted-foreground rounded-lg border border-dashed py-10 text-center text-sm">
                            Skema ini belum punya unit kompetensi.
                        </div>
                    ) : (
                        <ol className="divide-y rounded-lg border">
                            {units.map((unit) => (
                                <li key={unit.id_unit_kompetensi} className="flex items-start gap-3 p-3">
                                    <span className="bg-muted text-muted-foreground flex size-7 shrink-0 items-center justify-center rounded-md text-xs font-semibold tabular-nums">
                                        {unit.urutan}
                                    </span>

                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm leading-snug font-medium">{unit.judul}</p>
                                        <p className="text-muted-foreground mt-0.5 font-mono text-xs">{unit.kode}</p>
                                    </div>

                                    {canManage && (
                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-amber-600 hover:bg-amber-50 hover:text-amber-700 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                                title="Ubah unit"
                                                onClick={() => onEditUnit(unit)}
                                            >
                                                <Pencil />
                                                <span className="sr-only">Ubah {unit.kode}</span>
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                                                title="Hapus unit"
                                                onClick={() => onDeleteUnit(unit)}
                                            >
                                                <Trash2 />
                                                <span className="sr-only">Hapus {unit.kode}</span>
                                            </Button>
                                        </div>
                                    )}
                                </li>
                            ))}
                        </ol>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
