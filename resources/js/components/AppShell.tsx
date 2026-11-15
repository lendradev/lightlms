import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    GraduationCap,
    LayoutDashboard,
    LogOut,
    Settings,
    ShieldCheck,
    Users,
} from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { ReactNode } from 'react';
import type { AuthUser } from '@/types';

type NavItem = {
    label: string;
    href: string;
    icon: typeof LayoutDashboard;
    roles: string[];
};

const NAV_ITEMS: NavItem[] = [
    { label: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['student', 'instructor', 'admin'] },
    { label: 'My courses', href: '/learning', icon: GraduationCap, roles: ['student'] },
    { label: 'Teaching', href: '/teaching', icon: BookOpen, roles: ['instructor', 'admin'] },
    { label: 'Catalogue', href: '/courses', icon: BookOpen, roles: ['student', 'instructor', 'admin'] },
    { label: 'Users', href: '/admin/users', icon: Users, roles: ['admin'] },
    { label: 'Settings', href: '/admin/settings', icon: Settings, roles: ['admin'] },
];

export function AppShell({ title, actions, children }: { title: string; actions?: ReactNode; children: ReactNode }) {
    const { auth, app } = usePage().props as unknown as {
        auth: { user: AuthUser | null };
        app: { name: string };
    };

    const user = auth.user;
    const role = user?.role ?? 'student';

    return (
        <div className="flex min-h-screen bg-background">
            <aside className="hidden w-60 shrink-0 flex-col border-r bg-sidebar lg:flex">
                <div className="flex h-16 items-center gap-2.5 border-b px-5">
                    <span className="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                        L
                    </span>
                    <span className="font-serif text-lg font-semibold tracking-tight">{app.name}</span>
                </div>

                <nav className="flex-1 space-y-1 p-3" aria-label="Main">
                    {NAV_ITEMS.filter((item) => item.roles.includes(role)).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-sidebar-foreground transition hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                        >
                            <item.icon className="size-4 shrink-0" aria-hidden />
                            {item.label}
                        </Link>
                    ))}
                </nav>

                {user && (
                    <div className="border-t p-3">
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <button className="flex w-full items-center gap-3 rounded-lg px-2 py-2 text-left transition hover:bg-sidebar-accent">
                                    <Avatar className="size-8">
                                        <AvatarFallback>{user.name.slice(0, 2).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                    <span className="min-w-0 flex-1">
                                        <span className="block truncate text-sm font-medium">{user.name}</span>
                                        <span className="block truncate text-xs text-muted-foreground">
                                            {user.role}
                                        </span>
                                    </span>
                                </button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end" className="w-56">
                                <DropdownMenuLabel>{user.email}</DropdownMenuLabel>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/profile">
                                        <Users className="size-4" aria-hidden /> Profile
                                    </Link>
                                </DropdownMenuItem>
                                {role === 'admin' && (
                                    <DropdownMenuItem asChild>
                                        <Link href="/admin/settings">
                                            <ShieldCheck className="size-4" aria-hidden /> Administration
                                        </Link>
                                    </DropdownMenuItem>
                                )}
                                <DropdownMenuSeparator />
                                <DropdownMenuItem asChild>
                                    <Link href="/logout" method="post" as="button">
                                        <LogOut className="size-4" aria-hidden /> Sign out
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                )}
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="sticky top-0 z-40 flex h-16 items-center justify-between gap-4 border-b bg-card/85 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <h1 className="truncate font-serif text-lg font-semibold tracking-tight">{title}</h1>
                    <div className="flex items-center gap-2">
                        {actions}
                        {user && (
                            <span className="hidden text-sm text-muted-foreground sm:inline">{user.name}</span>
                        )}
                    </div>
                </header>

                <main className="flex-1 px-4 py-8 sm:px-6 lg:px-8">{children}</main>
            </div>
        </div>
    );
}
