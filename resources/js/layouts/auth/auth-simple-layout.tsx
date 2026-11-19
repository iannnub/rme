import AppLogoIcon from '@/components/app-logo-icon';
import { FlashToaster } from '@/components/flash-toaster';
import { Link } from '@inertiajs/react';

interface AuthLayoutProps {
    children: React.ReactNode;
    name?: string;
    title?: string;
    description?: string;
}

export default function AuthSimpleLayout({ children, title, description }: AuthLayoutProps) {
    return (
        <div className="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-4 sm:p-6 md:p-10">
            <header className="liquid-glass-header liquid-morph fixed inset-x-3 top-3 z-30 mx-auto flex max-w-4xl items-center justify-between rounded-2xl px-4 py-2.5 sm:px-6">
                <Link href={route('home')} className="flex items-center gap-2.5 font-semibold">
                    <div className="bg-primary text-primary-foreground flex h-9 w-9 items-center justify-center rounded-xl">
                        <AppLogoIcon className="size-5" />
                    </div>
                    <span className="text-foreground text-sm font-bold tracking-tight">RME OKA - Kamar Operasi</span>
                </Link>
                <span className="bg-secondary text-secondary-foreground rounded-lg px-2.5 py-1 text-xs font-semibold">
                    Sistem Rekam Medis Perioperatif
                </span>
            </header>

            <main id="main-content" className="w-full max-w-md pt-16">
                <div className="bg-card border-border flex flex-col gap-6 rounded-3xl border p-6 shadow-xs sm:p-8">
                    <div className="flex flex-col items-center gap-3">
                        <div className="bg-primary text-primary-foreground flex h-12 w-12 items-center justify-center rounded-2xl shadow-2xs">
                            <AppLogoIcon className="size-6" />
                        </div>

                        <div className="space-y-1 text-center">
                            <h1 className="text-foreground text-xl font-bold tracking-tight">{title}</h1>
                            <p className="text-muted-foreground text-center text-sm">{description}</p>
                        </div>
                    </div>
                    {children}
                </div>
            </main>
            <FlashToaster />
        </div>
    );
}
