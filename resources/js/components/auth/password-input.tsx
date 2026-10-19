import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { Eye, EyeOff } from 'lucide-react';
import * as React from 'react';

/**
 * Isian password dengan tombol mata sendiri. Setiap kolom punya toggle masing-masing,
 * jadi menampilkan satu kolom tidak ikut membuka kolom lainnya.
 */
const PasswordInput = React.forwardRef<HTMLInputElement, Omit<React.ComponentProps<'input'>, 'type'>>(({ className, ...props }, ref) => {
    const [visible, setVisible] = React.useState(false);

    return (
        <div className="relative">
            <Input ref={ref} type={visible ? 'text' : 'password'} className={cn('pr-10', className)} {...props} />

            <button
                type="button"
                onClick={() => setVisible((current) => !current)}
                className="text-muted-foreground hover:text-foreground absolute top-1/2 right-2 -translate-y-1/2 rounded-md p-1"
                aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'}
                aria-pressed={visible}
                tabIndex={-1}
            >
                {visible ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
            </button>
        </div>
    );
});

PasswordInput.displayName = 'PasswordInput';

export default PasswordInput;
