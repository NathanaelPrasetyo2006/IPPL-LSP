import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { LoaderCircle, TriangleAlert } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';

const KATA_KUNCI = 'hapus';

interface ConfirmDeleteDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: React.ReactNode;
    onConfirm: () => void;
    processing?: boolean;
    /** Keterangan tambahan, mis. bahwa data masih bisa dipulihkan. */
    note?: string;
}

/**
 * Konfirmasi hapus yang mewajibkan pengguna mengetik "hapus" agar data
 * tidak terhapus karena salah klik.
 */
export default function ConfirmDeleteDialog({
    open,
    onOpenChange,
    title,
    description,
    onConfirm,
    processing = false,
    note,
}: ConfirmDeleteDialogProps) {
    const [value, setValue] = useState('');

    // Kosongkan isian setiap kali dialog dibuka ulang.
    useEffect(() => {
        if (open) {
            setValue('');
        }
    }, [open]);

    const confirmed = value.trim().toLowerCase() === KATA_KUNCI;

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        if (confirmed && !processing) {
            onConfirm();
        }
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <form onSubmit={submit} className="grid gap-5">
                    <DialogHeader>
                        <div className="mb-2 flex size-10 items-center justify-center rounded-full bg-red-100 dark:bg-red-950/60">
                            <TriangleAlert className="size-5 text-red-600 dark:text-red-400" />
                        </div>
                        <DialogTitle>{title}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    {note && <p className="bg-muted/60 text-muted-foreground rounded-lg px-3 py-2 text-xs">{note}</p>}

                    <div className="grid gap-2">
                        <Label htmlFor="konfirmasi-hapus" className="font-normal">
                            Ketik <span className="font-mono font-semibold text-red-600 dark:text-red-400">{KATA_KUNCI}</span> untuk melanjutkan
                        </Label>
                        <Input
                            id="konfirmasi-hapus"
                            value={value}
                            onChange={(e) => setValue(e.target.value)}
                            autoComplete="off"
                            autoFocus
                            placeholder={KATA_KUNCI}
                            aria-invalid={value !== '' && !confirmed}
                        />
                    </div>

                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Batal
                        </Button>

                        <Button type="submit" variant="destructive" disabled={!confirmed || processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            Hapus
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
