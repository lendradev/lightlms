import { Link, usePage } from '@inertiajs/react';
import {
    Award,
    Bell,
    BookOpen,
    DatabaseBackup,
    GraduationCap,
    KeyRound,
    LayoutDashboard,
    LogOut,
    Mail,
    Menu,
    Settings,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeToggle } from '@/components/ThemeToggle';
import FlashMessage from '@/components/FlashMessage';
import type { AuthUser } from '@/types';

type NavItem = {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    roles: string[];
};

const NAV_ITEMS: NavItem[] = [
    { label: 'nav.dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['student', 'instructor', 'admin'] },
    { label: 'nav.myCourses', href: '/learning', icon: GraduationCap, roles: ['student'] },
    { label: 'nav.certificates', href: '/certificates', icon: Award, roles: ['student', 'instructor', 'admin'] },
    { label: 'nav.teaching', href: '/teaching', icon: BookOpen, roles: ['instructor', 'admin'] },
    { label: 'nav.catalogue', href: '/courses', icon: BookOpen, roles: ['student', 'instructor', 'admin'] },
    { label: 'nav.apiTokens', href: '/settings/api-tokens', icon: KeyRound, roles: ['student', 'instructor', 'admin'] },
    { label: 'nav.users', href: '/admin/users', icon: Users, roles: ['admin'] },
    { label: 'nav.invitations', href: '/admin/invitations', icon: Mail, roles: ['admin'] },
    { label: 'nav.backup', href: '/admin/backup', icon: DatabaseBackup, roles: ['admin'] },
    { label: 'nav.settings', href: '/admin/settings', icon: Settings, roles: ['admin'] },
];

export function AppShell({ title, actions, children }: { title: string; actions?: ReactNode; children: ReactNode }) {
    const { auth, app, notifications, translations } = usePage().props as unknown as {
        auth: { user: AuthUser | null };
        app: {
            name: string;
            locale: string;
            availableLocales: string[];
            localeNames?: Record<string, string>;
        };
        notifications: { unread: number };
        translations?: Record<string, string>;
    };

    const [menuOpen, setMenuOpen] = useState(false);

    const user = auth.user;
    const role = user?.role ?? 'student';
    const items = NAV_ITEMS.filter((item) => item.roles.includes(role));
    const unread = notifications?.unread ?? 0;

    return (
        <div className="flex min-h-screen bg-background">
            <aside className="sticky top-0 hidden h-screen w-60 shrink-0 flex-col border-r bg-sidebar lg:flex">
                <div className="flex h-14 shrink-0 items-center gap-2.5 border-b px-5">
                    <span className="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                        L
                    </span>
                    <span className="font-serif text-lg font-semibold tracking-tight">{app.name}</span>
                </div>

                <nav className="flex-1 space-y-1 overflow-y-auto p-3" aria-label="Main">
                    {items.map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-foreground transition hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <item.icon className="size-4 shrink-0" aria-hidden />
                            {translations?.[item.label] ?? item.label}
                        </Link>
                    ))}
                </nav>

                {user && (
                    <div className="shrink-0 border-t p-3">
                        <UserMenu user={user} />
                    </div>
                )}
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-40 flex h-14 shrink-0 items-center justify-between gap-3 border-b bg-card/85 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <div className="flex min-w-0 items-center gap-3">
                        <Sheet open={menuOpen} onOpenChange={setMenuOpen}>
                            <SheetTrigger asChild>
                                <Button variant="outline" size="icon" className="lg:hidden">
                                    <Menu aria-hidden />
                                    <span className="sr-only">{translations?.['nav.openNavigation'] ?? 'Open navigation'}</span>
                                </Button>
                            </SheetTrigger>
                            <SheetContent side="left" className="w-72 p-0">
                                <div className="flex h-14 items-center gap-2.5 border-b px-5">
                                    <span className="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                                        L
                                    </span>
                                    <SheetTitle className="font-serif text-lg font-semibold tracking-tight">
                                        {app.name}
                                    </SheetTitle>
                                </div>
                                <nav className="space-y-1 overflow-y-auto p-3" aria-label="Main">
                                    {items.map((item) => (
                                        <Link
                                            key={item.href}
                                            href={item.href}
                                            onClick={() => setMenuOpen(false)}
                                            className="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-sidebar-foreground transition hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                                        >
                                            <item.icon className="size-4 shrink-0" aria-hidden />
                                            {translations?.[item.label] ?? item.label}
                                        </Link>
                                    ))}
                                </nav>
                            </SheetContent>
                        </Sheet>

                        <h1 className="truncate font-serif text-lg font-semibold tracking-tight">{title}</h1>
                    </div>

                    <div className="flex items-center gap-2">
                        {actions}
                        {user && (
                            <Button asChild variant="ghost" size="icon" className="relative">
                                <Link
                                    href="/notifications"
                                    aria-label={`${translations?.['nav.notifications'] ?? 'Notifications'}, ${unread} unread`}
                                >
                                    <Bell className="size-4" aria-hidden />
                                    {unread > 0 && (
                                        <span className="absolute -right-0.5 -top-0.5 grid min-w-4 place-items-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">
                                            {unread > 9 ? '9+' : unread}
                                        </span>
                                    )}
                                </Link>
                            </Button>
                        )}
                        <LanguageSwitcher app={app} />
                        <ThemeToggle />
                        {user && (
                            <div className="lg:hidden">
                                <UserMenu user={user} compact />
                            </div>
                        )}
                    </div>
                </header>

                <main className="flex-1 px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
                    <FlashMessage className="mb-6" />
                    {children}
                </main>
            </div>
        </div>
    );
}

function UserMenu({ user, compact = false }: { user: AuthUser; compact?: boolean }) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <button
                    className={
                        compact
                            ? 'grid size-9 place-items-center rounded-full'
                            : 'flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-sidebar-accent'
                    }
                >
                    <Avatar className="size-8">
                        <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                    </Avatar>
                    {!compact && (
                        <span className="min-w-0 flex-1">
                            <span className="block truncate text-sm font-medium">{user.name}</span>
                            <span className="block truncate text-xs capitalize text-muted-foreground">
                                {user.role}
                            </span>
                        </span>
                    )}
                </button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
                <DropdownMenuLabel>{user.email}</DropdownMenuLabel>
                {user.role === 'admin' && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem asChild>
                            <Link href="/admin/settings">
                                <ShieldCheck className="size-4" aria-hidden /> Administration
                            </Link>
                        </DropdownMenuItem>
                    </>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem asChild>
                    <Link href="/logout" method="post" as="button">
                        <LogOut className="size-4" aria-hidden /> Sign out
                    </Link>
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
