import { Link } from '@inertiajs/react';
import { ThemeToggle } from '@/components/ThemeToggle';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';

type SiteHeaderProps = {
    user?: { name: string } | null;
};

const DOCS_URL = 'https://lendradev.github.io/lightlms/';
const REPO_URL = 'https://github.com/lendradev/lightlms';

export function SiteHeader({ user }: SiteHeaderProps) {
    return (
        <header className="sticky top-0 z-40 border-b border-border bg-card">
            <div className="container-page flex h-14 items-center justify-between gap-6">
                <Link href="/" className="flex items-center gap-2.5" aria-label="LightLMS home">
                    <span className="grid size-7 place-items-center rounded-sm bg-primary text-sm font-bold text-primary-foreground">
                        L
                    </span>
                    <span className="font-serif text-lg font-semibold tracking-tight text-foreground">
                        LightLMS
                    </span>
                </Link>

                <nav
                    className="hidden items-center gap-6 text-sm font-medium text-muted-foreground md:flex"
                    aria-label="Main"
                >
                    <Link href="/courses" className="transition hover:text-foreground">
                        Courses
                    </Link>
                    <Link href="/#features" className="transition hover:text-foreground">
                        Features
                    </Link>
                    <a href={DOCS_URL} className="transition hover:text-foreground">
                        Documentation
                    </a>
                </nav>

                <div className="flex items-center gap-3">
                    <ThemeToggle />
                    <Link
                        href={user ? '/dashboard' : '/login'}
                        className="hidden text-sm font-medium text-foreground transition hover:text-primary sm:inline"
                    >
                        {user ? user.name : 'Sign in'}
                    </Link>
                    <Button asChild size="sm">
                        <Link href={user ? '/dashboard' : '/courses'}>
                            {user ? 'Open dashboard' : 'See the catalogue'}
                        </Link>
                    </Button>
                </div>
            </div>
        </header>
    );
}

export function SiteFooter({ children }: { children?: ReactNode }) {
    return (
        <footer className="border-t border-border bg-card">
            <div className="container-page py-12">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="font-serif text-lg font-semibold text-foreground">LightLMS</p>
                        <p className="mt-2 max-w-xs text-sm leading-relaxed text-muted-foreground">
                            A self-hosted learning management system for schools and course teams. The
                            course record stays on your own server.
                        </p>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Product</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <Link href="/courses" className="transition hover:text-foreground">
                                    Catalogue
                                </Link>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}courses.html`} className="transition hover:text-foreground">
                                    Learner progress
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}certificates.html`} className="transition hover:text-foreground">
                                    Certificates
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Documentation</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <a href={`${DOCS_URL}quick-start.html`} className="transition hover:text-foreground">
                                    Quick start
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}configuration.html`} className="transition hover:text-foreground">
                                    Configuration
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}api.html`} className="transition hover:text-foreground">
                                    REST API
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">Project</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <a href={REPO_URL} className="transition hover:text-foreground">
                                    Repository
                                </a>
                            </li>
                            <li>
                                <a href={`${REPO_URL}/issues`} className="transition hover:text-foreground">
                                    Issue tracker
                                </a>
                            </li>
                            <li>
                                <a href={`${REPO_URL}/blob/main/CONTRIBUTING.md`} className="transition hover:text-foreground">
                                    Contributing
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} LightLMS contributors. MIT licensed.</p>
                    <p>Built with Laravel, Inertia.js and React.</p>
                </div>
                {children}
            </div>
        </footer>
    );
}
