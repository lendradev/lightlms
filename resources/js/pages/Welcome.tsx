import { Head, Link } from '@inertiajs/react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import type { CourseSummary, SharedProps } from '@/types';

type Props = {
    courses: CourseSummary[];
    auth: SharedProps['auth'];
};

export default function Welcome({ courses, auth }: Props) {
    return (
        <>
            <Head title="Open-source learning, built for the modern web" />

            <SiteHeader user={auth.user} />

            <main>
                <section className="border-b border-border bg-card">
                    <div className="container-page grid gap-12 py-20 lg:grid-cols-[1.05fr_0.95fr] lg:py-28">
                        <div className="max-w-2xl">
                            <Badge variant="secondary">Open source · MIT licensed</Badge>
                            <h1 className="mt-5 text-balance font-serif text-4xl font-semibold leading-tight tracking-tight text-foreground sm:text-5xl">
                                A learning platform that stays out of the way.
                            </h1>
                            <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                                LightLMS is a self-hosted LMS for courses, assignments and progress — built on
                                Laravel, Inertia.js and React. Deploy it on any Docker host and own your data.
                            </p>
                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild>
                                    <Link href="/courses">Explore the catalogue</Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href="#about">How it works</a>
                                </Button>
                            </div>
                        </div>

                        <Card className="hidden gap-0 overflow-hidden lg:flex">
                            <div className="border-b bg-muted px-5 py-3 text-xs font-medium text-muted-foreground">
                                Course workspace — 6.470 Introduction to Systems Thinking
                            </div>
                            <div className="space-y-1 p-4">
                                {[
                                    { label: 'Module 1 · Core Concepts', state: 'done' },
                                    { label: 'Module 2 · Feedback Loops', state: 'active' },
                                    { label: 'Module 3 · Modelling Systems', state: 'todo' },
                                    { label: 'Module 4 · Case Studies', state: 'todo' },
                                ].map((row) => (
                                    <div
                                        key={row.label}
                                        className="flex items-center justify-between rounded-lg px-3 py-2.5 text-sm"
                                    >
                                        <span
                                            className={
                                                row.state === 'active'
                                                    ? 'font-medium text-foreground'
                                                    : 'text-muted-foreground'
                                            }
                                        >
                                            {row.label}
                                        </span>
                                        {row.state === 'done' ? (
                                            <Badge variant="secondary">Complete</Badge>
                                        ) : row.state === 'active' ? (
                                            <Badge>In progress</Badge>
                                        ) : (
                                            <Badge variant="outline">Not started</Badge>
                                        )}
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>
                </section>

                <section className="container-page py-16">
                    <div className="flex items-end justify-between gap-4">
                        <div>
                            <h2 className="font-serif text-2xl font-semibold text-foreground">
                                Recently published
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Fresh courses from the LightLMS catalogue.
                            </p>
                        </div>
                        <Link href="/courses" className="text-sm font-semibold text-primary hover:underline">
                            View all →
                        </Link>
                    </div>

                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {courses.map((course) => (
                            <CourseCard key={course.id} course={course} />
                        ))}
                    </div>
                </section>

                <section id="about" className="border-t border-border bg-card">
                    <div className="container-page grid gap-10 py-16 md:grid-cols-3">
                        {[
                            {
                                title: 'Calm by default',
                                body: 'A restrained visual language inspired by MIT OpenCourseWare — generous whitespace, one clear accent colour, no dashboard noise.',
                            },
                            {
                                title: 'Own your data',
                                body: 'Self-host with a single compose file. Postgres for relational data, S3-compatible storage for uploads, no vendor lock-in.',
                            },
                            {
                                title: 'Built to extend',
                                body: 'A conventional Laravel application. Every feature is a package, a service class or a resource you can read, replace and test.',
                            },
                        ].map((item) => (
                            <div key={item.title}>
                                <h3 className="font-serif text-lg font-semibold text-foreground">{item.title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{item.body}</p>
                            </div>
                        ))}
                    </div>
                </section>
            </main>

            <SiteFooter />
        </>
    );
}
