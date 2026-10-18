import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData, type UserRole } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ClipboardCheck, Database, FileSignature, IdCard, LayoutGrid } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        url: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'Master Data',
        url: '/master',
        icon: Database,
        // Asesor melihat skema & TUK dalam mode baca saja.
        roles: ['admin', 'asesor'],
        items: [
            {
                title: 'Skema Sertifikasi',
                url: '/master/skema-sertifikasi',
            },
            {
                title: 'Lokasi TUK',
                url: '/master/tuk',
            },
            {
                title: 'Akun Asesor',
                url: '/master/asesor',
                roles: ['admin'],
            },
        ],
    },
    {
        title: 'Pendaftaran',
        url: '/pendaftaran',
        icon: ClipboardCheck,
        roles: ['admin'],
        items: [
            {
                title: 'Periode Pendaftaran',
                url: '/pendaftaran/periode',
            },
            {
                title: 'Verifikasi APL-01',
                url: '/pendaftaran/verifikasi',
            },
        ],
    },
    {
        title: 'Pendaftaran Sertifikasi',
        url: '/sertifikasi',
        icon: FileSignature,
        roles: ['asesi'],
    },
    {
        title: 'Profil Asesor',
        url: '/asesor',
        icon: IdCard,
        roles: ['asesor'],
        items: [
            {
                title: 'Biodata',
                url: '/asesor/biodata',
            },
            {
                title: 'Kartu Tanda Asesor',
                url: '/asesor/kartu',
            },
        ],
    },
];

/** Saring menu (termasuk submenu) sesuai peran pengguna. */
function filterByRole(items: NavItem[], role: UserRole): NavItem[] {
    return items
        .filter((item) => !item.roles || item.roles.includes(role))
        .map((item) => (item.items ? { ...item, items: filterByRole(item.items, role) } : item));
}

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const navItems = filterByRole(mainNavItems, auth.user.role);

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={navItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
