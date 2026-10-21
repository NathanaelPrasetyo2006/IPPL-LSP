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
    [key: string]: string;
}

export default function UnitKompetensiDialog({ open, onOpenChange, skema, unit }: UnitKompetensiDialogProps) {
    const { data, setData, post, put, processing, errors, clearErrors } = useForm<UnitForm>({ kode: '', judul: '' });

    // Isi ulang form setiap dialog dibuka, sesuai unit yang dipilih.
    useEffect(() => {
        if (!open) {
            return;
        }

        clearErrors();
        setData({
            kode: unit?.kode ?? '',
            judul: unit?.judul ?? '',
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
            put(route('master.unit-kompetensi.update', unit.ulid), options);
        } else {
            post(route('master.skema-sertifikasi.unit-kompetensi.store', skema.ulid), options);
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

                    <div className="grid gap-2">
                        <Label htmlFor="unit-kode">Kode unit</Label>
                        <Input
                            id="unit-kode"
                            value={data.kode}
                            // Kode tanpa spasi dan berhuruf besar: spasi dibuang dan huruf diubah kapital saat diketik/ditempel.
                            onChange={(e) => setData('kode', e.target.value.replace(/\s/g, '').toUpperCase())}
                            required
                            autoFocus
                            autoComplete="off"
                            placeholder="Contoh: J.620100.001.01"
                        />
                        {!unit && <p className="text-muted-foreground text-xs">Unit baru ditempatkan di urutan paling akhir.</p>}
                        <InputError message={errors.kode} />
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
