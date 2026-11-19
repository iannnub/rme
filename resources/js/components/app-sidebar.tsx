import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { getNavGroups } from '@/lib/navigation';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const page = usePage<SharedData>();
    const currentPath = page.url.split('?')[0];
    const navGroups = getNavGroups(page.props.auth.user?.role);

    return (
        <Sidebar collapsible="icon" variant="floating">
            <SidebarHeader className="p-2.5">
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild className="liquid-morph-pill rounded-2xl">
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent className="px-1.5">
                {navGroups.map((group) => (
                    <SidebarGroup key={group.title} className="px-2 py-1">
                        <SidebarGroupLabel className="text-muted-foreground text-xs font-semibold">{group.title}</SidebarGroupLabel>
                        <SidebarMenu className="gap-1">
                            {group.items.map((item) => {
                                const active =
                                    currentPath === item.url ||
                                    (item.url !== '/dashboard' && item.url !== '/pengaturan' && currentPath.startsWith(item.url));

                                return (
                                    <SidebarMenuItem key={item.title}>
                                        <SidebarMenuButton
                                            asChild
                                            isActive={active}
                                            className={cn(
                                                'liquid-morph-pill min-h-11 rounded-xl px-3 font-medium',
                                                active
                                                    ? 'bg-primary! text-primary-foreground! font-semibold shadow-xs'
                                                    : 'text-foreground/85 hover:bg-secondary! hover:text-foreground!',
                                            )}
                                        >
                                            <Link href={item.url} prefetch aria-current={active ? 'page' : undefined}>
                                                {item.icon && <item.icon aria-hidden="true" />}
                                                <span>{item.title}</span>
                                            </Link>
                                        </SidebarMenuButton>
                                    </SidebarMenuItem>
                                );
                            })}
                        </SidebarMenu>
                    </SidebarGroup>
                ))}
            </SidebarContent>

            <SidebarFooter className="p-2.5">
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
