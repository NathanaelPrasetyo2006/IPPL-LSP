import { LucideIcon } from 'lucide-react';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    url: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: NavItem[];
    /** Batasi menu ke peran tertentu; kosong berarti tampil untuk semua peran. */
    roles?: UserRole[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    flash: Flash;
    notifikasi: NotifikasiShared | null;
    [key: string]: unknown;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
    status?: string | null;
}

export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    prev_page_url: string | null;
    next_page_url: string | null;
    current_page: number;
    from: number | null;
    to: number | null;
    per_page: number;
    last_page: number;
    total: number;
}

export interface SkemaSertifikasi {
    id_skema: number;
    judul: string;
    nomor: string;
    unit_kompetensi_count?: number;
    unit_kompetensi?: UnitKompetensi[];
}

export interface UnitKompetensi {
    id_unit_kompetensi: number;
    id_skema: number;
    kode: string;
    judul: string;
    urutan: number;
}

export type UserRole = 'admin' | 'asesor' | 'asesi';

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown; // This allows for additional properties...
}

export interface Tuk {
    id_tuk: number;
    nama: string;
    alamat: string;
}

export interface AsesorAccount {
    id_asesor: number;
    kode_asesor: string | null;
    nama: string;
    no_reg: string;
    email: string | null;
    dibuat_pada: string | null;
    status: AsesorStatus;
}

/** Tahap akun asesor: menunggu verifikasi email → masih password sementara → aktif. */
export type AsesorStatus = 'menunggu_verifikasi' | 'password_sementara' | 'aktif';

/** Biodata yang diisi sendiri oleh asesor dari portalnya. */
export interface AsesorBiodata {
    kode_asesor: string | null;
    nama: string;
    no_reg: string;
    email: string;
    lengkap: boolean;
    foto_url: string | null;
    nik: string | null;
    tempat_lahir: string | null;
    tgl_lahir: string | null;
    jenis_kelamin: string | null;
    no_telp: string | null;
    alamat: string | null;
    kota: string | null;
    provinsi: string | null;
    pendidikan_terakhir: string | null;
    instansi: string | null;
    bidang_keahlian: string | null;
    masa_berlaku_sertifikat: string | null;
    diperbarui_pada: string | null;
}

export interface AsesorBiodataOptions {
    jenis_kelamin: Record<string, string>;
    pendidikan: Record<string, string>;
}

/** Data yang dicetak pada Kartu Tanda Asesor. */
export interface KartuAsesor {
    kode_asesor: string | null;
    nama: string;
    no_reg: string;
    lengkap: boolean;
    foto_url: string | null;
    bidang_keahlian: string | null;
    instansi: string | null;
    masa_berlaku_sertifikat: string | null;
    kedaluwarsa: boolean;
}

export interface NotifikasiItem {
    id: string;
    judul: string;
    pesan: string;
    tipe: 'info' | 'sukses' | 'gagal' | 'peringatan';
    dibaca: boolean;
    waktu: string | null;
}

export interface NotifikasiShared {
    belum_dibaca: number;
    terbaru: NotifikasiItem[];
}

export type StatusPeriode = 'dibuka' | 'dijadwalkan' | 'ditutup';

export interface SkemaOption {
    id_skema: number;
    judul: string;
    nomor: string;
    unit_kompetensi_count?: number;
}

export interface PeriodePendaftaran {
    id_periode: number;
    nama: string;
    tanggal_buka: string;
    tanggal_tutup: string;
    kuota: number | null;
    kuota_terpakai: number;
    aktif: boolean;
    status: StatusPeriode;
    skema: SkemaOption[];
    jumlah_permohonan: number;
    permohonan_menunggu: number;
}

export type StatusPermohonan = 'diajukan' | 'diterima' | 'ditolak';

export interface PermohonanRingkas {
    id_permohonan: number;
    nomor: string;
    status: StatusPermohonan;
    status_label: string;
    skema: { id_skema: number; judul: string; nomor: string } | null;
    periode: { id_periode: number; nama: string } | null;
    lokasi: Tuk | null;
    /** Permohonan berjalan yang lokasinya dikosongkan admin dan harus dipilih ulang oleh asesi. */
    perlu_pilih_lokasi: boolean;
    nama_asesi: string | null;
    kode_asesi: string | null;
    diajukan_pada: string | null;
    diverifikasi_pada: string | null;
}

export interface DokumenPermohonan {
    jenis: string;
    label: string;
    wajib: boolean;
    identitas: boolean;
    ada: boolean;
    nama_file: string | null;
    ukuran: number | null;
    url: string | null;
}

export interface PermohonanDetail extends Omit<PermohonanRingkas, 'skema'> {
    tujuan_asesmen: string;
    catatan_verifikasi: string | null;
    cek_dokumen: Record<string, boolean>;
    verifikator: string | null;
    skema: { id_skema: number; judul: string; nomor: string; unit_kompetensi: { id_unit_kompetensi: number; kode: string; judul: string }[] };
    pemohon: Record<string, string | null> & { nama_lengkap: string; kode_asesi: string };
    dokumen: DokumenPermohonan[];
    riwayat_lokasi: RiwayatLokasi[];
}

export interface RiwayatLokasi {
    id_riwayat: number;
    waktu: string | null;
    lokasi_lama: string | null;
    lokasi_baru: string | null;
    keterangan: string;
    oleh: string | null;
}

/** Definisi jenis dokumen APL-01 dari server (JenisDokumen::definisi). */
export interface JenisDokumenDef {
    jenis: string;
    label: string;
    wajib: boolean;
    identitas: boolean;
    accept: string;
    maks_mb: number;
}

/** Keadaan kode OTP dari server (KodeVerifikasi::status), dalam detik. */
export interface OtpStatus {
    terkirim: boolean;
    kedaluwarsa_dalam: number;
    kirim_ulang_dalam: number;
    sisa_kirim_ulang: number;
}
