import { Link, usePage } from '@inertiajs/react';
import { LanguageSwitcher } from '@/components/LanguageSwitcher';
import { ThemeToggle } from '@/components/ThemeToggle';
import { Button } from '@/components/ui/button';
import type { ReactNode } from 'react';

type SiteHeaderProps = {
    user?: { name: string } | null;
};

type PageProps = {
    app: {
        locale: string;
        availableLocales: string[];
        localeNames?: Record<string, string>;
    };
    translations?: Record<string, string>;
};

const DOCS_URL = 'https://lendradev.github.io/lightlms/';
const REPO_URL = 'https://github.com/lendradev/lightlms';

export function SiteHeader({ user }: SiteHeaderProps) {
    const { app, translations } = usePage().props as unknown as PageProps;
    const t = (key: string) => translations?.[key] ?? key;

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
                        {t('app.courses')}
                    </Link>
                    <Link href="/#features" className="transition hover:text-foreground">
                        {t('landing.featuresLink')}
                    </Link>
                    <a href={DOCS_URL} className="transition hover:text-foreground">
                        {t('app.documentation')}
                    </a>
                </nav>

                <div className="flex items-center gap-3">
                    <LanguageSwitcher app={app} />
                    <ThemeToggle />
                    <Link
                        href={user ? '/dashboard' : '/login'}
                        className="hidden text-sm font-medium text-foreground transition hover:text-primary sm:inline"
                    >
                        {user ? user.name : t('app.signIn')}
                    </Link>
                    <Button asChild size="sm">
                        <Link href={user ? '/dashboard' : '/courses'}>
                            {user ? t('app.openDashboard') : t('app.seeCatalogue')}
                        </Link>
                    </Button>
                </div>
            </div>
        </header>
    );
}

export function SiteFooter({ children }: { children?: ReactNode }) {
    const { translations } = usePage().props as unknown as PageProps;
    const t = (key: string) => translations?.[key] ?? key;

    return (
        <footer className="border-t border-border bg-card">
            <div className="container-page py-12">
                <div className="grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <p className="font-serif text-lg font-semibold text-foreground">LightLMS</p>
                        <p className="mt-2 max-w-xs text-sm leading-relaxed text-muted-foreground">
                            {t('app.tagline')}
                        </p>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">{t('app.product')}</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <Link href="/courses" className="transition hover:text-foreground">
                                    {t('app.catalogue')}
                                </Link>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}courses.html`} className="transition hover:text-foreground">
                                    {t('app.learnerProgress')}
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}certificates.html`} className="transition hover:text-foreground">
                                    {t('app.certificates')}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">{t('app.documentation')}</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <a href={`${DOCS_URL}quick-start.html`} className="transition hover:text-foreground">
                                    {t('app.quickStart')}
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}configuration.html`} className="transition hover:text-foreground">
                                    {t('app.configuration')}
                                </a>
                            </li>
                            <li>
                                <a href={`${DOCS_URL}api.html`} className="transition hover:text-foreground">
                                    {t('app.restApi')}
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <p className="text-sm font-semibold text-foreground">{t('app.project')}</p>
                        <ul className="mt-3 space-y-2 text-sm text-muted-foreground">
                            <li>
                                <a href={REPO_URL} className="transition hover:text-foreground">
                                    {t('app.repository')}
                                </a>
                            </li>
                            <li>
                                <a href={`${REPO_URL}/issues`} className="transition hover:text-foreground">
                                    {t('app.issueTracker')}
                                </a>
                            </li>
                            <li>
                                <a href={`${REPO_URL}/blob/main/CONTRIBUTING.md`} className="transition hover:text-foreground">
                                    {t('app.contributing')}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div className="mt-10 flex flex-col gap-2 border-t border-border pt-6 text-xs text-muted-foreground sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        © {new Date().getFullYear()} LightLMS contributors. {t('app.licensed')}
                    </p>
                    <p>{t('app.builtWith')}</p>
                </div>
                {children}
            </div>
        </footer>
    );
}
