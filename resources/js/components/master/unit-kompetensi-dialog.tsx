import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type SkemaSertifikasi, type UnitKompetensi } from '@/types';
import { useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect } from 'react';

interface UnitKompetensiDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    skema: SkemaSertifikasi | null;
    /** Diisi saat mengubah unit; kosong berarti menambah unit baru. */
    unit: UnitKompetensi | null;
}

interface UnitForm {
    kode: string;
    judul: string;
    urutan: string;
    [key: string]: string;
}

function nextUrutan(skema: SkemaSertifikasi | null): number {
    return Math.max(0, ...(skema?.unit_kompetensi ?? []).map((unit) => unit.urutan)) + 1;
}

export default function UnitKompetensiDialog({ open, onOpenChange, skema, unit }: UnitKompetensiDialogProps) {
    const { data, setData, post, put, processing, errors, clearErrors } = useForm<UnitForm>({ kode: '', judul: '', urutan: '' });

    // Isi ulang form setiap dialog dibuka, sesuai unit yang dipilih.
    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData({
            kode: unit?.kode ?? '',
            judul: unit?.judul ?? '',
            urutan: String(unit?.urutan ?? nextUrutan(skema)),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open, unit, skema?.id_skema]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (!skema) {
            return;
        }

        // preserveState menjaga popup detail skema tetap terbuka setelah disimpan.
        const options = { preserveScroll: true, preserveState: true, onSuccess: () => onOpenChange(false) };

        if (unit) {
            put(route('master.unit-kompetensi.update', unit.id_unit_kompetensi), options);
        } else {
            post(route('master.skema-sertifikasi.unit-kompetensi.store', skema.id_skema), options);
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <form onSubmit={submit} className="grid gap-5">
                    <DialogHeader>
                        <DialogTitle>{unit ? 'Ubah Unit Kompetensi' : 'Tambah Unit Kompetensi'}</DialogTitle>
                        <DialogDescription>
                            Skema <span className="text-foreground font-medium">{skema?.nomor}</span> &middot; {skema?.judul}
                        </DialogDescription>
                    </DialogHeader>

                    <div className="grid gap-4 sm:grid-cols-[1fr_7rem]">
                        <div className="grid gap-2">
                            <Label htmlFor="unit-kode">Kode unit</Label>
                            <Input
                                id="unit-kode"
                                value={data.kode}
                                onChange={(e) => setData('kode', e.target.value)}
                                required
                                autoFocus
                                autoComplete="off"
                                placeholder="Contoh: J.620100.001.01"
                            />
                            <InputError message={errors.kode} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="unit-urutan">Urutan</Label>
                            <Input
                                id="unit-urutan"
                                type="number"
                                min={1}
                                max={999}
                                value={data.urutan}
                                onChange={(e) => setData('urutan', e.target.value)}
                                required
                            />
                            <InputError message={errors.urutan} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="unit-judul">Judul unit kompetensi</Label>
                        <Input
                            id="unit-judul"
                            value={data.judul}
                            onChange={(e) => setData('judul', e.target.value)}
                            required
                            autoComplete="off"
                            placeholder="Contoh: Menganalisis Tools"
                        />
                        <InputError message={errors.judul} />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>

                        <Button type="submit" disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {unit ? 'Simpan perubahan' : 'Tambah unit'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
