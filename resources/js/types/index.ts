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
    [key: string]: unknown;
}

export interface Flash {
    success?: string | null;
    error?: string | null;
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
    kode: string;
    nama: string;
    jenis: string;
    alamat: string;
    kota: string;
    penanggung_jawab: string | null;
    telepon: string | null;
    status: string;
}

export interface TukOptions {
    jenis: Record<string, string>;
    status: Record<string, string>;
}

export interface AsesorAccount {
    id_asesor: number;
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
    nama: string;
    no_reg: string;
    lengkap: boolean;
    foto_url: string | null;
    bidang_keahlian: string | null;
    instansi: string | null;
    masa_berlaku_sertifikat: string | null;
    kedaluwarsa: boolean;
}
