import RoleAvatar from '@/components/auth/role-avatar';
import { ROLE_ORDER, ROLES } from '@/components/auth/roles';
import { buttonVariants } from '@/components/ui/button';
import { AuthBrand } from '@/layouts/auth/auth-simple-layout';
import { cn } from '@/lib/utils';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export default function LoginPortal() {
    return (
        <div className="bg-muted/40 flex min-h-svh flex-col items-center px-4 py-10 md:px-10 md:py-16">
            <Head title="Portal Masuk" />

            <div className="flex w-full max-w-4xl flex-1 flex-col items-center justify-center">
                <AuthBrand />

                <div className="mt-8 space-y-2 text-center">
                    <h1 className="text-2xl font-semibold tracking-tight md:text-3xl">Portal Sistem Informasi LSP</h1>
                    <p className="text-muted-foreground text-sm">Masuk sebagai</p>
                </div>

                <div className="mt-10 grid w-full gap-4 sm:grid-cols-3 md:gap-6">
                    {ROLE_ORDER.map((role) => {
                        const meta = ROLES[role];

                        return (
                            <Link
                                key={role}
                                href={route('login.role', role)}
                                className={cn(
                                    'group bg-card flex flex-col items-center rounded-2xl border p-6 text-center shadow-xs transition-all duration-200 outline-none',
                                    'hover:-translate-y-1 hover:shadow-lg focus-visible:-translate-y-1 focus-visible:shadow-lg',
                                    meta.hoverClass,
                                )}
                            >
                                <div
                                    className={cn(
                                        'size-28 overflow-hidden rounded-full transition-transform duration-200 group-hover:scale-105',
                                        meta.avatarClass,
                                    )}
                                >
                                    <RoleAvatar role={role} />
                                </div>

                                <h2 className="mt-5 text-base font-semibold">{meta.label}</h2>
                                {/* flex-1 menjaga tombol tetap sejajar walau panjang deskripsi berbeda. */}
                                <p className="text-muted-foreground mt-1.5 mb-6 flex-1 text-sm text-balance">{meta.summary}</p>

                                <span className={cn(buttonVariants(), 'w-full')}>
                                    Masuk
                                    <ArrowRight className="size-4 transition-transform group-hover:translate-x-0.5" />
                                </span>
                            </Link>
                        );
                    })}
                </div>
            </div>

            <p className="text-muted-foreground mt-10 text-center text-xs">
                Copyright © {new Date().getFullYear()} LSP Global · Professional Certification
            </p>
        </div>
    );
}
