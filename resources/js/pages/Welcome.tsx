import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Button } from '@/components/ui/button';
import type { CourseSummary, SharedProps } from '@/types';

type Props = {
    courses: CourseSummary[];
    auth: SharedProps['auth'];
};

type PageProps = {
    translations?: Record<string, string>;
};

const DOCS_URL = 'https://lendradev.github.io/lightlms/';
const REPO_URL = 'https://github.com/lendradev/lightlms';

const FACTS = [
    { value: '14', label: 'landing.fact1' },
    { value: '3', label: 'landing.fact2' },
    { value: 'MIT', label: 'landing.fact3' },
];

const FEATURES = [
    { title: 'landing.feature1Title', body: 'landing.feature1Body' },
    { title: 'landing.feature2Title', body: 'landing.feature2Body' },
    { title: 'landing.feature3Title', body: 'landing.feature3Body' },
    { title: 'landing.feature4Title', body: 'landing.feature4Body' },
];

export default function Welcome({ courses, auth }: Props) {
    const { translations } = usePage().props as unknown as PageProps;
    const t = (key: string) => translations?.[key] ?? key;

    return (
        <>
            <Head title="Self-hosted learning management" />

            <SiteHeader user={auth.user} />

            <main>
                <section className="border-b border-border bg-card">
                    <div className="container-page grid items-start gap-12 py-14 lg:grid-cols-[0.9fr_1.1fr] lg:py-20">
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">
                                {t('landing.eyebrow')}
                            </p>
                            <h1 className="mt-4 text-balance font-serif text-4xl font-semibold leading-[1.1] tracking-tight text-foreground sm:text-5xl">
                                {t('landing.headline')}
                            </h1>
                            <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                                {t('landing.lede')}
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild>
                                    <Link href="/courses">
                                        {t('app.seeCatalogue')}
                                        <ArrowRight aria-hidden />
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={`${DOCS_URL}quick-start.html`}>{t('app.readDocs')}</a>
                                </Button>
                            </div>

                            <dl className="mt-10 grid gap-6 border-t border-border pt-8 sm:grid-cols-3">
                                {FACTS.map((fact) => (
                                    <div key={fact.label}>
                                        <dt className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                                            {fact.value}
                                        </dt>
                                        <dd className="mt-1 text-sm leading-relaxed text-muted-foreground">
                                            {t(fact.label)}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                        </div>

                        <figure className="lg:pt-2">
                            <img
                                src="/images/landing/hero-course.png"
                                alt="The course player: modules on the left, lessons in the middle and a progress panel on the right."
                                className="w-full rounded-md border border-border shadow-lifted dark:hidden"
                                width={780}
                                height={489}
                            />
                            <img
                                src="/images/landing/hero-course-dark.png"
                                alt="The course player: modules on the left, lessons in the middle and a progress panel on the right."
                                className="hidden w-full rounded-md border border-border shadow-lifted dark:block"
                                width={780}
                                height={489}
                            />
                        </figure>
                    </div>
                </section>

                <section id="features" className="container-page py-16 lg:py-24">
                    <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
                        <div>
                            <h2 className="text-balance font-serif text-3xl font-semibold tracking-tight text-foreground">
                                {t('landing.featuresTitle')}
                            </h2>
                            <p className="mt-4 max-w-sm leading-relaxed text-muted-foreground">
                                {t('landing.featuresLede')}
                            </p>
                        </div>

                        <dl className="grid gap-x-10 gap-y-8 sm:grid-cols-2">
                            {FEATURES.map((feature) => (
                                <div key={feature.title}>
                                    <dt className="font-serif text-lg font-semibold text-foreground">
                                        {t(feature.title)}
                                    </dt>
                                    <dd className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {t(feature.body)}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </section>

                <section className="border-y border-border bg-card">
                    <div className="container-page grid items-center gap-12 py-16 lg:grid-cols-2 lg:py-24">
                        <figure>
                            <img
                                src="/images/landing/course-builder.png"
                                alt="The course builder: sections with their activities, and the enrolled students with their progress."
                                className="w-full rounded-md border border-border shadow-soft dark:hidden"
                                width={1000}
                                height={616}
                            />
                            <img
                                src="/images/landing/course-builder-dark.png"
                                alt="The course builder: sections with their activities, and the enrolled students with their progress."
                                className="hidden w-full rounded-md border border-border shadow-soft dark:block"
                                width={1000}
                                height={616}
                            />
                        </figure>

                        <div>
                            <h2 className="text-balance font-serif text-3xl font-semibold tracking-tight text-foreground">
                                {t('landing.builderTitle')}
                            </h2>
                            <p className="mt-4 leading-relaxed text-muted-foreground">
                                {t('landing.builderLede')}
                            </p>
                            <Link
                                href="/courses"
                                className="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
                            >
                                {t('app.seeCatalogue')}
                                <ArrowRight className="size-4" aria-hidden />
                            </Link>
                        </div>
                    </div>
                </section>

                <section className="container-page py-16 lg:py-24">
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h2 className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                                {t('landing.publishedTitle')}
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                {t('landing.publishedLede')}
                            </p>
                        </div>
                        <Link
                            href="/courses"
                            className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:underline"
                        >
                            {t('app.seeCatalogue')}
                            <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </div>

                    {courses.length > 0 ? (
                        <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {courses.map((course) => (
                                <CourseCard key={course.id} course={course} />
                            ))}
                        </div>
                    ) : (
                        <p className="mt-8 rounded-md border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
                            {t('landing.publishedEmpty')}
                        </p>
                    )}
                </section>

                <section className="border-t border-border bg-card">
                    <div className="container-page py-16 lg:py-20">
                        <h2 className="text-balance font-serif text-3xl font-semibold tracking-tight text-foreground">
                            {t('landing.closeTitle')}
                        </h2>
                        <p className="mt-3 max-w-xl leading-relaxed text-muted-foreground">
                            {t('landing.closeLede')}
                        </p>
                        <div className="mt-7 flex flex-wrap gap-3">
                            <Button asChild>
                                <a href={`${DOCS_URL}quick-start.html`}>
                                    {t('landing.quickStart')}
                                    <ArrowRight aria-hidden />
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <a href={REPO_URL}>{t('app.openRepository')}</a>
                            </Button>
                        </div>
                    </div>
                </section>
            </main>

            <SiteFooter />
        </>
    );
}
