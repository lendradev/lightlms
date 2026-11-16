import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Button } from '@/components/ui/button';
import type { CourseSummary, SharedProps } from '@/types';

type Props = {
    courses: CourseSummary[];
    auth: SharedProps['auth'];
};

const DOCS_URL = 'https://lendradev.github.io/lightlms/';
const REPO_URL = 'https://github.com/lendradev/lightlms';

const FACTS = [
    { value: '14', label: 'REST endpoints under /api/v1' },
    { value: '3', label: 'roles per course, plus a site admin' },
    { value: 'MIT', label: 'licence, no vendor in the middle' },
];

const FEATURES = [
    {
        title: 'Courses, modules and activities',
        body: 'A course holds modules; a module holds readings, videos, quizzes, assignments and forum threads. Learners mark each activity complete and the course progress follows.',
    },
    {
        title: 'Grading with weights and categories',
        body: 'Give every item a weight, group the items into categories, and export the sheet as CSV. Manual marks, feedback and letter grades sit in the same view.',
    },
    {
        title: 'Roles that fail closed',
        body: 'Site roles and course roles are separate. Every state-changing endpoint checks the course membership on the server and records the action in the audit log.',
    },
    {
        title: 'Your own backup',
        body: 'One compose file starts the app, Postgres, Redis and a queue worker. The administrator can export the database with the uploaded files and restore it later.',
    },
];

export default function Welcome({ courses, auth }: Props) {
    return (
        <>
            <Head title="Self-hosted learning management" />

            <SiteHeader user={auth.user} />

            <main>
                <section className="border-b border-border bg-card">
                    <div className="container-page grid items-start gap-12 py-14 lg:grid-cols-[0.9fr_1.1fr] lg:py-20">
                        <div>
                            <p className="text-sm font-medium text-muted-foreground">
                                Open source · MIT licensed
                            </p>
                            <h1 className="mt-4 text-balance font-serif text-4xl font-semibold leading-[1.1] tracking-tight text-foreground sm:text-5xl">
                                Self-hosted learning management for schools and course teams
                            </h1>
                            <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                                LightLMS runs courses, assignments, quizzes and grading on your own
                                server. The student record never leaves your infrastructure.
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild>
                                    <Link href="/courses">
                                        See the catalogue
                                        <ArrowRight aria-hidden />
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href={`${DOCS_URL}quick-start.html`}>Read the documentation</a>
                                </Button>
                            </div>

                            <dl className="mt-10 grid gap-6 border-t border-border pt-8 sm:grid-cols-3">
                                {FACTS.map((fact) => (
                                    <div key={fact.label}>
                                        <dt className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                                            {fact.value}
                                        </dt>
                                        <dd className="mt-1 text-sm leading-relaxed text-muted-foreground">
                                            {fact.label}
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
                                What the platform does
                            </h2>
                            <p className="mt-4 max-w-sm leading-relaxed text-muted-foreground">
                                Four areas cover the ordinary work of a school: the course, the work
                                handed in, the mark, and the record of who did what.
                            </p>
                        </div>

                        <dl className="grid gap-x-10 gap-y-8 sm:grid-cols-2">
                            {FEATURES.map((feature) => (
                                <div key={feature.title}>
                                    <dt className="font-serif text-lg font-semibold text-foreground">
                                        {feature.title}
                                    </dt>
                                    <dd className="mt-2 text-sm leading-relaxed text-muted-foreground">
                                        {feature.body}
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
                                Build a course and see who is behind
                            </h2>
                            <p className="mt-4 leading-relaxed text-muted-foreground">
                                The builder writes the course as you work: add a section, add an
                                activity, publish it. The roster sits beside it, with each learner and
                                the share of the course they have finished.
                            </p>
                            <Link
                                href="/courses"
                                className="mt-6 inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
                            >
                                See the catalogue
                                <ArrowRight className="size-4" aria-hidden />
                            </Link>
                        </div>
                    </div>
                </section>

                <section className="container-page py-16 lg:py-24">
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h2 className="font-serif text-3xl font-semibold tracking-tight text-foreground">
                                Published courses
                            </h2>
                            <p className="mt-2 text-muted-foreground">
                                Recent courses from this installation.
                            </p>
                        </div>
                        <Link
                            href="/courses"
                            className="inline-flex shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:underline"
                        >
                            See the catalogue
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
                            No published course yet. Sign in as an instructor to publish one.
                        </p>
                    )}
                </section>

                <section className="border-t border-border bg-card">
                    <div className="container-page py-16 lg:py-20">
                        <h2 className="text-balance font-serif text-3xl font-semibold tracking-tight text-foreground">
                            Run it on your own host
                        </h2>
                        <p className="mt-3 max-w-xl leading-relaxed text-muted-foreground">
                            One compose file, one database, and the documentation to go with it.
                        </p>
                        <div className="mt-7 flex flex-wrap gap-3">
                            <Button asChild>
                                <a href={`${DOCS_URL}quick-start.html`}>
                                    Read the quick start
                                    <ArrowRight aria-hidden />
                                </a>
                            </Button>
                            <Button asChild variant="outline">
                                <a href={REPO_URL}>Open the repository</a>
                            </Button>
                        </div>
                    </div>
                </section>
            </main>

            <SiteFooter />
        </>
    );
}
