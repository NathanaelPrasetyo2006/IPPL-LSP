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
}

export interface User {
    id: number;
    name: string;
    email: string;
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
