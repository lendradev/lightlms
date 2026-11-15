import { Link } from '@inertiajs/react';
import type { ReactNode } from 'react';

type SiteHeaderProps = {
    user?: { name: string } | null;
};

export function SiteHeader({ user }: SiteHeaderProps) {
    return (
        <header className="sticky top-0 z-40 border-b border-ink-200 bg-white/85 backdrop-blur">
            <div className="container-page flex h-16 items-center justify-between gap-6">
                <Link href="/" className="flex items-center gap-2.5" aria-label="LightLMS home">
                    <span className="grid size-8 place-items-center rounded-md bg-brand-600 text-sm font-bold text-white">
                        L
                    </span>
                    <span className="font-serif text-lg font-semibold tracking-tight text-ink-900">LightLMS</span>
                </Link>

                <nav className="hidden items-center gap-6 text-sm font-medium text-ink-600 md:flex" aria-label="Main">
                    <Link href="/courses" className="transition hover:text-ink-900">
                        Explore courses
                    </Link>
                    <a href="#about" className="transition hover:text-ink-900">
                        About
                    </a>
                </nav>

                <div className="flex items-center gap-3">
                    {user ? (
                        <span className="hidden text-sm text-ink-600 sm:inline">{user.name}</span>
                    ) : (
                        <span className="hidden text-sm text-ink-600 sm:inline">Sign in</span>
                    )}
                    <Link href="/courses" className="btn-primary">
                        Get started
                    </Link>
                </div>
            </div>
        </header>
    );
}

export function SiteFooter({ children }: { children?: ReactNode }) {
    return (
        <footer className="mt-24 border-t border-ink-200 bg-white">
            <div className="container-page py-12">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="font-serif text-lg font-semibold text-ink-900">LightLMS</p>
                        <p className="mt-2 max-w-xs text-sm leading-relaxed text-ink-500">
                            A modern, self-hosted open-source learning platform for schools, universities and
                            independent educators.
                        </p>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-ink-900">Product</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-500">
                            <li>Course catalogue</li>
                            <li>Assignments &amp; quizzes</li>
                            <li>Progress tracking</li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-ink-900">Resources</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-500">
                            <li>Documentation</li>
                            <li>Deployment guide</li>
                            <li>Contributing</li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-ink-900">Community</p>
                        <ul className="mt-3 space-y-2 text-sm text-ink-500">
                            <li>GitHub discussions</li>
                            <li>Issue tracker</li>
                            <li>Code of conduct</li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-ink-200 pt-6 text-xs text-ink-500 sm:flex-row sm:items-center sm:justify-between">
                    <p>© {new Date().getFullYear()} LightLMS contributors. Released under the MIT License.</p>
                    <p>Built with Laravel, Inertia.js and React.</p>
                </div>
                {children}
            </div>
        </footer>
    );
}
