import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';

type SiteHeaderProps = {
    user?: { name: string } | null;
};

export function SiteHeader({ user }: SiteHeaderProps) {
    return (
        <header className="sticky top-0 z-40 border-b border-border bg-card/85 backdrop-blur">
            <div className="container-page flex h-16 items-center justify-between gap-6">
                <Link href="/" className="flex items-center gap-2.5" aria-label="LightLMS home">
                    <span className="grid size-8 place-items-center rounded-md bg-primary text-sm font-bold text-primary-foreground">
                        L
                    </span>
                    <span className="font-serif text-lg font-semibold tracking-tight text-foreground">LightLMS</span>
                </Link>

                <nav className="hidden items-center gap-6 text-sm font-medium text-muted-foreground md:flex" aria-label="Main">
                    <Link href="/courses" className="transition hover:text-foreground">
                        Explore courses
                    </Link>
                    <a href="#about" className="transition hover:text-foreground">
                        About
                    </a>
                </nav>

                <div className="flex items-center gap-3">
                    {user ? (
                        <span className="hidden text-sm text-muted-foreground sm:inline">{user.name}</span>
                    ) : (
                        <span className="hidden text-sm text-muted-foreground sm:inline">Sign in</span>
                    )}
                    <Button asChild size="sm">
                        <Link href="/courses">Get started</Link>
                    </Button>
                </div>
            </div>
        </header>
    );
}

export function SiteFooter({ children }: { children?: ReactNode }) {
    return (
        <footer className="mt-24 border-t border-border bg-card">
            <div className="container-page py-12">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="font-serif text-lg font-semibold text-foreground">LightLMS</p>
                        <p className="mt-2 max-w-xs text-sm leading-relaxed text-muted-foreground">
                            A modern, self-hosted open-source learning platform for schools, universities and
                            independent educators.
                        </p>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Product</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>Course catalogue</li>
                            <li>Assignments &amp; quizzes</li>
                            <li>Progress tracking</li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Resources</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>Documentation</li>
                            <li>Deployment guide</li>
                            <li>Contributing</li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Community</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>GitHub discussions</li>
                            <li>Issue tracker</li>
                            <li>Code of conduct</li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} LightLMS contributors. Released under the MIT License.</p>
                    <p>Built with Laravel, Inertia.js and React.</p>
                </div>
                {children}
            </div>
        </footer>
    );
}
