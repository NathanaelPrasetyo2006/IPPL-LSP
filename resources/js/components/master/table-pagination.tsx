import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

interface TablePaginationProps {
    from: number | null;
    to: number | null;
    total: number;
    prevPageUrl: string | null;
    nextPageUrl: string | null;
}

export default function TablePagination({ from, to, total, prevPageUrl, nextPageUrl }: TablePaginationProps) {
    return (
        <div className="flex items-center justify-between border-t p-4">
            <p className="text-muted-foreground text-sm">
                Menampilkan {from ?? 0}&ndash;{to ?? 0} dari {total} data
            </p>

            <div className="flex gap-2">
                {prevPageUrl ? (
                    <Button variant="outline" size="icon" className="size-9" asChild>
                        <Link href={prevPageUrl} preserveScroll preserveState>
                            <ChevronLeft />
                            <span className="sr-only">Halaman sebelumnya</span>
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="icon" className="size-9" disabled>
                        <ChevronLeft />
                    </Button>
                )}

                {nextPageUrl ? (
                    <Button variant="outline" size="icon" className="size-9" asChild>
                        <Link href={nextPageUrl} preserveScroll preserveState>
                            <ChevronRight />
                            <span className="sr-only">Halaman berikutnya</span>
                        </Link>
                    </Button>
                ) : (
                    <Button variant="outline" size="icon" className="size-9" disabled>
                        <ChevronRight />
                    </Button>
                )}
            </div>
        </div>
    );
}
