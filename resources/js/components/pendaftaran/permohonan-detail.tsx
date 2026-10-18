import { cn } from '@/lib/utils';
import { type DokumenPermohonan, type PermohonanDetail } from '@/types';
import { CircleCheck, CircleX, ExternalLink, FileText, FileWarning } from 'lucide-react';

export function Panel({
    title,
    description,
    children,
    className,
}: {
    title: string;
    description?: string;
    children: React.ReactNode;
    className?: string;
}) {
    return (
        <section className={cn('bg-card rounded-xl border shadow-xs', className)}>
            <div className="border-b px-5 py-4">
                <h2 className="font-semibold">{title}</h2>
                {description && <p className="text-muted-foreground mt-0.5 text-sm">{description}</p>}
            </div>
            <div className="p-5">{children}</div>
        </section>
    );
}

function Baris({ label, value }: { label: string; value: React.ReactNode }) {
    return (
        <div className="grid gap-0.5 sm:grid-cols-[180px_1fr] sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium break-words">{value || <span className="text-muted-foreground font-normal">-</span>}</dd>
        </div>
    );
}

/** Bagian 1 FR-APL-01: rincian data pemohon. */
export function DataPemohon({ pemohon }: { pemohon: PermohonanDetail['pemohon'] }) {
    return (
        <Panel title="Bagian 1 · Data Pemohon" description="Rincian data pribadi dan pekerjaan pemohon sertifikasi.">
            <dl className="grid gap-3">
                <Baris label="Kode asesi" value={pemohon.kode_asesi} />
                <Baris label="Nama lengkap" value={pemohon.nama_lengkap} />
                <Baris label="NIK" value={pemohon.nik} />
                <Baris label="Tempat, tgl. lahir" value={pemohon.tempat_tgl_lahir} />
                <Baris label="Jenis kelamin" value={pemohon.jenis_kelamin} />
                <Baris label="Kebangsaan" value={pemohon.kebangsaan} />
                <Baris label="Alamat (KTP)" value={pemohon.alamat_ktp} />
                <Baris label="Alamat domisili" value={pemohon.alamat_domisili} />
                <Baris label="No. HP" value={pemohon.no_telp} />
                <Baris label="Email" value={pemohon.email} />
                <Baris label="Pendidikan terakhir" value={pemohon.pendidikan} />
                <Baris label="Pekerjaan" value={pemohon.pekerjaan} />
            </dl>

            <h3 className="text-muted-foreground mt-6 mb-3 text-xs font-semibold tracking-wide uppercase">Data pekerjaan sekarang</h3>
            <dl className="grid gap-3">
                <Baris label="Institusi / perusahaan" value={pemohon.nama_institusi} />
                <Baris label="Jabatan" value={pemohon.jabatan} />
                <Baris label="Alamat kantor" value={pemohon.alamat_kantor} />
                <Baris label="Telepon kantor" value={pemohon.telp_kantor} />
                <Baris label="Email kantor" value={pemohon.email_kantor} />
            </dl>
        </Panel>
    );
}

/** Bagian 2 FR-APL-01: data sertifikasi. */
export function DataSertifikasi({ permohonan }: { permohonan: PermohonanDetail }) {
    return (
        <Panel title="Bagian 2 · Data Sertifikasi">
            <dl className="grid gap-3">
                <Baris label="Skema" value={permohonan.skema.judul} />
                <Baris label="Nomor skema" value={permohonan.skema.nomor} />
                <Baris label="Periode" value={permohonan.periode?.nama} />
                <Baris label="Tujuan asesmen" value={permohonan.tujuan_asesmen} />
            </dl>

            <h3 className="text-muted-foreground mt-6 mb-3 text-xs font-semibold tracking-wide uppercase">
                Daftar unit kompetensi ({permohonan.skema.unit_kompetensi.length})
            </h3>

            {permohonan.skema.unit_kompetensi.length === 0 ? (
                <p className="text-muted-foreground text-sm">Skema ini belum memiliki unit kompetensi.</p>
            ) : (
                <ol className="divide-y rounded-lg border">
                    {permohonan.skema.unit_kompetensi.map((unit, index) => (
                        <li key={unit.id_unit_kompetensi} className="flex gap-3 px-3 py-2 text-sm">
                            <span className="text-muted-foreground w-5 shrink-0 tabular-nums">{index + 1}.</span>
                            <span className="min-w-0">
                                <span className="text-muted-foreground block font-mono text-xs">{unit.kode}</span>
                                {unit.judul}
                            </span>
                        </li>
                    ))}
                </ol>
            )}
        </Panel>
    );
}

function formatUkuran(byte: number | null): string | null {
    if (!byte) {
        return null;
    }

    return byte >= 1024 * 1024 ? `${(byte / 1024 / 1024).toFixed(1)} MB` : `${Math.ceil(byte / 1024)} KB`;
}

interface DokumenListProps {
    dokumen: DokumenPermohonan[];
    /** Hasil cek verifikator per jenis dokumen; ditampilkan bila permohonan sudah diputuskan. */
    cek?: Record<string, boolean>;
    /** Render kontrol tambahan di kanan setiap baris (dipakai verifikator untuk centang "memenuhi"). */
    aksi?: (dokumen: DokumenPermohonan) => React.ReactNode;
}

/** Bagian 3 FR-APL-01: bukti kelengkapan pemohon. */
export function DokumenList({ dokumen, cek, aksi }: DokumenListProps) {
    const kelompok = [
        { judul: 'Bukti administratif', items: dokumen.filter((item) => item.identitas) },
        { judul: 'Bukti persyaratan dasar', items: dokumen.filter((item) => !item.identitas) },
    ];

    return (
        <div className="grid gap-5">
            {kelompok.map(({ judul, items }) => (
                <div key={judul}>
                    <h3 className="text-muted-foreground mb-2 text-xs font-semibold tracking-wide uppercase">{judul}</h3>

                    <ul className="divide-y rounded-lg border">
                        {items.map((item) => {
                            const hasilCek = cek?.[item.jenis];

                            return (
                                <li key={item.jenis} className="flex flex-col gap-3 px-3 py-3 sm:flex-row sm:items-center">
                                    <span
                                        className={cn(
                                            'flex size-9 shrink-0 items-center justify-center rounded-lg',
                                            item.ada
                                                ? 'bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400'
                                                : 'bg-muted text-muted-foreground',
                                            !item.ada && item.wajib && 'bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400',
                                        )}
                                    >
                                        {item.ada ? <FileText className="size-4" /> : <FileWarning className="size-4" />}
                                    </span>

                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-medium">
                                            {item.label}
                                            {item.wajib ? (
                                                <span className="ml-1.5 text-xs font-normal text-red-600 dark:text-red-400">wajib</span>
                                            ) : (
                                                <span className="text-muted-foreground ml-1.5 text-xs font-normal">opsional</span>
                                            )}
                                        </p>

                                        {item.ada && item.url ? (
                                            <a
                                                href={item.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="text-muted-foreground hover:text-foreground inline-flex max-w-full items-center gap-1 text-xs hover:underline"
                                            >
                                                <span className="truncate">{item.nama_file}</span>
                                                {formatUkuran(item.ukuran) && <span className="shrink-0">· {formatUkuran(item.ukuran)}</span>}
                                                <ExternalLink className="size-3 shrink-0" />
                                            </a>
                                        ) : (
                                            <p className="text-muted-foreground text-xs">Tidak diunggah</p>
                                        )}
                                    </div>

                                    {aksi
                                        ? aksi(item)
                                        : hasilCek !== undefined &&
                                          item.ada && (
                                              <span
                                                  className={cn(
                                                      'inline-flex items-center gap-1 text-xs font-medium',
                                                      hasilCek ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400',
                                                  )}
                                              >
                                                  {hasilCek ? <CircleCheck className="size-3.5" /> : <CircleX className="size-3.5" />}
                                                  {hasilCek ? 'Memenuhi' : 'Tidak memenuhi'}
                                              </span>
                                          )}
                                </li>
                            );
                        })}
                    </ul>
                </div>
            ))}
        </div>
    );
}
