import AppLogoIcon from '@/components/app-logo-icon';
import { cn } from '@/lib/utils';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
    /** Lebar kartu, mis. "max-w-md" untuk formulir yang lebih panjang. */
    className?: string;
    /** Konten di atas judul kartu, mis. penanda peran. */
    header?: React.ReactNode;
}

export function AuthBrand() {
    return (
        <Link href={route('login')} className="flex items-center justify-center gap-3">
            <div className="bg-sidebar-primary flex size-10 shrink-0 items-center justify-center rounded-xl">
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>

            <div className="text-left">
                <div className="text-base leading-tight font-bold">LSP Global</div>
                <div className="text-muted-foreground text-[11px] leading-tight">Professional Certification</div>
            </div>
        </Link>
    );
}

export default function AuthSimpleLayout({ children, title, description, className, header }: AuthLayoutProps) {
    return (
        <div className="bg-muted/40 flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div className={cn('w-full max-w-sm', className)}>
                <div className="flex flex-col gap-6">
                    <AuthBrand />

                    <div className="bg-card rounded-xl border p-6 shadow-xs">
                        {header}

                        <div className="mb-6 space-y-1.5">
                            <h1 className="text-xl font-semibold tracking-tight">{title}</h1>
                            <p className="text-muted-foreground text-sm">{description}</p>
                        </div>

                        {children}
                    </div>
                </div>
            </div>
        </div>
    );
}
