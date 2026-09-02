import { SidebarGroup, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function NavMain({ items = [] }: { items: NavItem[] }) {
    const page = usePage();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarMenu className="gap-1">
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            // Menu dianggap aktif juga saat berada di halaman turunannya (create/edit).
                            isActive={page.url === item.url || page.url.startsWith(`${item.url}/`)}
                            className="h-10 data-[active=true]:bg-violet-600 data-[active=true]:text-white data-[active=true]:shadow-sm data-[active=true]:hover:bg-violet-600 data-[active=true]:hover:text-white"
                        >
                            <Link href={item.url} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
