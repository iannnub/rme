import { getNavGroups } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export function MobileBottomNav() {
    const page = usePage<SharedData>();
    const currentPath = page.url.split('?')[0];
    const navGroups = getNavGroups(page.props.auth.user?.role);

    const flatItems = navGroups.flatMap((group) => group.items).slice(0, 5);

    if (flatItems.length === 0) {
        return null;
    }

    return (
        <nav
            aria-label="Navigasi bawah perangkat bergerak"
            className="liquid-glass-nav liquid-morph fixed inset-x-3 bottom-3 z-40 flex items-center justify-around gap-1 rounded-3xl p-1.5 md:hidden"
        >
            {flatItems.map((item) => {
                const active =
                    currentPath === item.url || (item.url !== '/dashboard' && item.url !== '/pengaturan' && currentPath.startsWith(item.url));

                return (
                    <Link
                        key={item.url}
                        href={item.url}
                        aria-current={active ? 'page' : undefined}
                        className={cn(
                            'liquid-morph-pill flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 rounded-2xl px-2 py-1.5 text-[11px] font-medium',
                            active
                                ? 'bg-primary text-primary-foreground font-semibold shadow-xs'
                                : 'text-foreground/80 hover:bg-secondary hover:text-foreground',
                        )}
                    >
                        {item.icon && <item.icon className="h-4 w-4 shrink-0" aria-hidden="true" />}
                        <span className="max-w-[72px] truncate leading-tight">{item.title}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
