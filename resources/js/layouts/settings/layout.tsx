import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function SettingsLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage<SharedData>().props;
    const currentPath = typeof window !== 'undefined' ? window.location.pathname : '';

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profil',
            url: '/pengaturan',
            icon: null,
        },
        {
            title: 'Kata Sandi',
            url: '/pengaturan/sandi',
            icon: null,
        },
        {
            title: 'Tampilan',
            url: '/pengaturan/tampilan',
            icon: null,
        },
        ...(auth.user?.role === 'admin'
            ? [
                  {
                      title: 'Data Rumah Sakit',
                      url: '/pengaturan/rumah-sakit',
                      icon: null,
                  },
              ]
            : []),
    ];

    return (
        <div className="px-4 py-6">
            <Heading title="Pengaturan" description="Kelola informasi profil, kata sandi, dan tampilan akun Anda" />

            <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-52">
                    <nav className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item) => (
                            <Button
                                key={item.url}
                                size="default"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-accent text-accent-foreground font-medium': currentPath === item.url,
                                })}
                            >
                                <Link href={item.url} prefetch>
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 md:hidden" />

                <div className="min-w-0 flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </div>
    );
}
