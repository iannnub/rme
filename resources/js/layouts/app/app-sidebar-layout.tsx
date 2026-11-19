import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { FlashToaster } from '@/components/flash-toaster';
import { MobileBottomNav } from '@/components/mobile-bottom-nav';
import { SessionTimeoutWarning } from '@/components/session-timeout-warning';
import { type BreadcrumbItem } from '@/types';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: { children: React.ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    return (
        <AppShell variant="sidebar">
            <a
                href="#main-content"
                className="focus:bg-primary focus:text-primary-foreground sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-50 focus:rounded-xl focus:px-4 focus:py-2.5 focus:text-sm focus:font-semibold focus:shadow-lg"
            >
                Lewati ke konten utama
            </a>
            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                <div id="main-content" tabIndex={-1} className="flex min-w-0 flex-1 flex-col pb-24 focus:outline-none md:pb-6">
                    {children}
                </div>
            </AppContent>
            <MobileBottomNav />
            <FlashToaster />
            <SessionTimeoutWarning />
        </AppShell>
    );
}
