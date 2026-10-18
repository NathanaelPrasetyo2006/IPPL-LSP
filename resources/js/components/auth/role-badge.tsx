import RoleAvatar from '@/components/auth/role-avatar';
import { ROLES } from '@/components/auth/roles';
import { cn } from '@/lib/utils';
import { type UserRole } from '@/types';
import { Link } from '@inertiajs/react';
import { ArrowLeftRight } from 'lucide-react';

/**
 * Penanda peran di bagian atas kartu formulir auth, dengan tautan kembali ke portal.
 */
export default function RoleBadge({ role, caption = 'Portal' }: { role: UserRole; caption?: string }) {
    const meta = ROLES[role];

    return (
        <div className="bg-muted/50 mb-6 flex items-center gap-3 rounded-lg border p-2.5">
            <div className={cn('size-11 shrink-0 overflow-hidden rounded-full', meta.avatarClass)}>
                <RoleAvatar role={role} />
            </div>

            <div className="min-w-0 flex-1">
                <p className="text-muted-foreground text-[11px] leading-tight">{caption}</p>
                <p className="truncate text-sm leading-tight font-semibold">{meta.label}</p>
            </div>

            <Link
                href={route('login')}
                className="text-muted-foreground hover:text-foreground hover:bg-background inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-xs font-medium transition-colors"
            >
                <ArrowLeftRight className="size-3.5" />
                Ganti peran
            </Link>
        </div>
    );
}
