import { cn } from '@/lib/utils';
import { type Tuk } from '@/types';
import { MapPin } from 'lucide-react';

interface PilihLokasiProps {
    lokasi: Tuk[];
    value: string;
    onChange: (value: string) => void;
}

/** Pilihan lokasi uji (TUK) dalam bentuk kartu radio, dipakai di APL-01 dan saat memilih ulang lokasi. */
export default function PilihLokasi({ lokasi, value, onChange }: PilihLokasiProps) {
    return (
        <div className="grid gap-2 sm:grid-cols-2" role="radiogroup">
            {lokasi.map((tuk) => {
                const id = tuk.id_tuk.toString();

                return (
                    <label
                        key={id}
                        className={cn(
                            'hover:bg-muted/50 bg-background flex cursor-pointer items-start gap-3 rounded-lg border px-3 py-2.5',
                            value === id && 'border-primary bg-muted/50',
                        )}
                    >
                        <input
                            type="radio"
                            name="id_tuk"
                            value={id}
                            checked={value === id}
                            onChange={() => onChange(id)}
                            className="accent-primary mt-0.5 size-4"
                        />
                        <span className="min-w-0 flex-1">
                            <span className="flex items-center gap-1.5 text-sm font-medium">
                                <MapPin className="text-muted-foreground size-3.5 shrink-0" />
                                {tuk.nama}
                            </span>
                            <span className="text-muted-foreground mt-0.5 block text-xs whitespace-pre-line">{tuk.alamat}</span>
                        </span>
                    </label>
                );
            })}
        </div>
    );
}
