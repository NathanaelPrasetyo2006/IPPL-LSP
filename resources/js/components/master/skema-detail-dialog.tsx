import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { type SharedData, type SkemaSertifikasi, type UnitKompetensi } from '@/types';
import { closestCenter, DndContext, type DragEndEvent, KeyboardSensor, PointerSensor, useSensor, useSensors } from '@dnd-kit/core';
import { restrictToVerticalAxis } from '@dnd-kit/modifiers';
import { arrayMove, SortableContext, sortableKeyboardCoordinates, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { router, usePage } from '@inertiajs/react';
import { CircleCheck, GripVertical, LayoutList, ListPlus, LoaderCircle, Pencil, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';

interface SkemaDetailDialogProps {
    skema: SkemaSertifikasi | null;
    onOpenChange: (open: boolean) => void;
    /** Aksi kelola hanya tampil untuk admin. */
    canManage: boolean;
    onAddUnit: () => void;
    onEditUnit: (unit: UnitKompetensi) => void;
}

const BELUM_DISIMPAN = 'Simpan atau batalkan perubahan urutan terlebih dahulu.';

interface UnitRowProps {
    unit: UnitKompetensi;
    nomor: number;
    canManage: boolean;
    terkunci: boolean;
    onEdit: () => void;
    onDelete: () => void;
}

function UnitRow({ unit, nomor, canManage, terkunci, onEdit, onDelete }: UnitRowProps) {
    const { attributes, listeners, setNodeRef, setActivatorNodeRef, transform, transition, isDragging } = useSortable({
        id: unit.id_unit_kompetensi,
        disabled: !canManage,
    });

    return (
        <li
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={cn('bg-card flex items-start gap-3 p-3', isDragging && 'relative z-10 rounded-lg shadow-lg ring-1 ring-blue-500/50')}
        >
            {canManage && (
                <button
                    type="button"
                    ref={setActivatorNodeRef}
                    {...attributes}
                    {...listeners}
                    title="Seret untuk mengubah urutan"
                    className="text-muted-foreground hover:bg-muted hover:text-foreground focus-visible:ring-ring -ml-1 flex size-7 shrink-0 cursor-grab touch-none items-center justify-center rounded-md focus-visible:ring-2 focus-visible:outline-hidden active:cursor-grabbing"
                >
                    <GripVertical className="size-4" />
                    <span className="sr-only">Pindahkan {unit.kode}</span>
                </button>
            )}

            <span className="bg-muted text-muted-foreground flex size-7 shrink-0 items-center justify-center rounded-md text-xs font-semibold tabular-nums">
                {nomor}
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
                        title={terkunci ? BELUM_DISIMPAN : 'Ubah unit'}
                        disabled={terkunci}
                        onClick={onEdit}
                    >
                        <Pencil />
                        <span className="sr-only">Ubah {unit.kode}</span>
                    </Button>

                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8 text-red-600 hover:bg-red-50 hover:text-red-700 dark:text-red-400 dark:hover:bg-red-950/50"
                        title="Hapus unit"
                        onClick={onDelete}
                    >
                        <Trash2 />
                        <span className="sr-only">Hapus {unit.kode}</span>
                    </Button>
                </div>
            )}
        </li>
    );
}

export default function SkemaDetailDialog({ skema, onOpenChange, canManage, onAddUnit, onEditUnit }: SkemaDetailDialogProps) {
    const { flash } = usePage<SharedData>().props;
    const asal = skema?.unit_kompetensi ?? [];

    // Drag and drop serta hapus hanya mengubah daftar lokal; baru tersimpan saat admin menekan Simpan.
    const [units, setUnits] = useState<UnitKompetensi[]>(asal);
    const [dihapus, setDihapus] = useState<UnitKompetensi[]>([]);
    const [menyimpan, setMenyimpan] = useState(false);
    const [tersimpan, setTersimpan] = useState(false);

    // Data dari server (dialog dibuka, unit ditambah/diubah, atau selesai disimpan) menjadi titik awal baru.
    useEffect(() => {
        setUnits(skema?.unit_kompetensi ?? []);
        setDihapus([]);
    }, [skema]);

    useEffect(() => {
        if (!tersimpan) {
            return;
        }

        const timeout = setTimeout(() => setTersimpan(false), 2500);

        return () => clearTimeout(timeout);
    }, [tersimpan]);

    const urutanBerubah = units.some(
        (unit, index) => unit.id_unit_kompetensi !== asal.filter((item) => !dihapus.includes(item))[index]?.id_unit_kompetensi,
    );
    const adaPerubahan = dihapus.length > 0 || urutanBerubah;

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 4 } }),
        useSensor(KeyboardSensor, { coordinateGetter: sortableKeyboardCoordinates }),
    );

    const handleDragEnd = ({ active, over }: DragEndEvent) => {
        if (!over || active.id === over.id) {
            return;
        }

        setUnits((current) => {
            const dari = current.findIndex((unit) => unit.id_unit_kompetensi === active.id);
            const ke = current.findIndex((unit) => unit.id_unit_kompetensi === over.id);

            return arrayMove(current, dari, ke);
        });
    };

    const hapus = (unit: UnitKompetensi) => {
        setUnits((current) => current.filter((item) => item.id_unit_kompetensi !== unit.id_unit_kompetensi));
        setDihapus((current) => [...current, unit]);
    };

    const batal = () => {
        setUnits(asal);
        setDihapus([]);
    };

    const simpan = () => {
        if (!skema) {
            return;
        }

        router.put(
            route('master.skema-sertifikasi.unit-kompetensi.susun', skema.ulid),
            {
                urutan: units.map((unit) => unit.id_unit_kompetensi),
                hapus: dihapus.map((unit) => unit.id_unit_kompetensi),
            },
            {
                preserveScroll: true,
                // Popup tetap terbuka; daftar diisi ulang dari props baru lewat efek di atas.
                preserveState: true,
                onStart: () => setMenyimpan(true),
                onSuccess: () => setTersimpan(true),
                onFinish: () => setMenyimpan(false),
            },
        );
    };

    const tutup = (open: boolean) => {
        if (!open && adaPerubahan && !window.confirm('Perubahan urutan unit kompetensi belum disimpan. Tutup tanpa menyimpan?')) {
            return;
        }

        onOpenChange(open);
    };

    return (
        <Dialog open={skema !== null} onOpenChange={tutup}>
            <DialogContent className="flex max-h-[92svh] flex-col gap-0 overflow-hidden p-0 sm:max-w-5xl">
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
                    <div>
                        <h3 className="text-sm font-semibold">Daftar Unit Kompetensi</h3>
                        {canManage && units.length > 1 && (
                            <p className="text-muted-foreground mt-0.5 text-xs">Seret ikon ⋮⋮ untuk mengubah urutan. Nomor menyesuaikan otomatis.</p>
                        )}
                    </div>

                    {canManage && (
                        <Button size="sm" onClick={onAddUnit} disabled={adaPerubahan} title={adaPerubahan ? BELUM_DISIMPAN : undefined}>
                            <ListPlus />
                            Tambah Unit
                        </Button>
                    )}
                </div>

                <div className="min-h-0 flex-1 overflow-y-auto px-6 pb-6">
                    {canManage && flash.error && (
                        <p className="border-destructive/40 bg-destructive/5 text-destructive mb-3 rounded-lg border px-3 py-2 text-sm">
                            {flash.error}
                        </p>
                    )}

                    {units.length === 0 ? (
                        <div className="text-muted-foreground rounded-lg border border-dashed py-10 text-center text-sm">
                            {dihapus.length > 0 ? 'Semua unit akan dihapus setelah disimpan.' : 'Skema ini belum punya unit kompetensi.'}
                        </div>
                    ) : (
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            modifiers={[restrictToVerticalAxis]}
                            onDragEnd={handleDragEnd}
                            // Daftar ikut bergulir saat unit diseret mendekati tepi atas/bawah.
                            autoScroll={{ threshold: { x: 0, y: 0.15 }, acceleration: 20 }}
                        >
                            <SortableContext items={units.map((unit) => unit.id_unit_kompetensi)} strategy={verticalListSortingStrategy}>
                                <ol className="divide-y rounded-lg border">
                                    {units.map((unit, index) => (
                                        <UnitRow
                                            key={unit.id_unit_kompetensi}
                                            unit={unit}
                                            nomor={index + 1}
                                            canManage={canManage}
                                            terkunci={adaPerubahan}
                                            onEdit={() => onEditUnit(unit)}
                                            onDelete={() => hapus(unit)}
                                        />
                                    ))}
                                </ol>
                            </SortableContext>
                        </DndContext>
                    )}
                </div>

                {canManage && (adaPerubahan || tersimpan) && (
                    <div
                        className={cn(
                            'flex flex-col gap-3 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between',
                            adaPerubahan ? 'bg-amber-50 dark:bg-amber-950/30' : 'bg-emerald-50 dark:bg-emerald-950/30',
                        )}
                    >
                        {adaPerubahan ? (
                            <>
                                <div className="text-sm text-amber-900 dark:text-amber-200">
                                    <p className="font-medium">Perubahan belum disimpan</p>
                                    <p className="text-xs opacity-90">
                                        {[
                                            urutanBerubah && 'urutan diubah',
                                            dihapus.length > 0 &&
                                                `${dihapus.length} unit akan dihapus (${dihapus.map((unit) => unit.kode).join(', ')})`,
                                        ]
                                            .filter(Boolean)
                                            .join(' · ')}
                                    </p>
                                </div>

                                <div className="flex shrink-0 gap-2">
                                    <Button variant="outline" size="sm" onClick={batal} disabled={menyimpan}>
                                        Batal
                                    </Button>
                                    <Button size="sm" onClick={simpan} disabled={menyimpan}>
                                        {menyimpan && <LoaderCircle className="size-4 animate-spin" />}
                                        Simpan
                                    </Button>
                                </div>
                            </>
                        ) : (
                            <p className="flex items-center gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-300">
                                <CircleCheck className="size-4" />
                                Perubahan tersimpan.
                            </p>
                        )}
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}
