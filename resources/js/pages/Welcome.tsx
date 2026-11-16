import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2, LayoutDashboard, ShieldCheck, Smartphone } from 'lucide-react';
import CourseCard from '@/components/CourseCard';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
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
                    <div className="container-page grid items-center gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr] lg:py-20">
                        <div>
                            <Badge variant="secondary">Open source · MIT licensed</Badge>
                            <h1 className="mt-5 text-balance font-serif text-4xl font-semibold leading-[1.1] tracking-tight text-foreground sm:text-5xl">
                                A learning platform that stays out of the way.
                            </h1>
                            <p className="mt-5 max-w-xl text-lg leading-relaxed text-muted-foreground">
                                LightLMS is a self-hosted LMS for courses, assignments and progress — built on
                                Laravel, Inertia.js and React. Deploy it on any Docker host and own your data.
                            </p>

                            <div className="mt-8 flex flex-wrap gap-3">
                                <Button asChild>
                                    <Link href="/courses">
                                        Explore the catalogue
                                        <ArrowRight aria-hidden />
                                    </Link>
                                </Button>
                                <Button asChild variant="outline">
                                    <a href="#features">How it works</a>
                                </Button>
                            </div>

                            <ul className="mt-8 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                                {['Self-hosted', 'Mobile first', 'No vendor lock-in'].map((point) => (
                                    <li key={point} className="inline-flex items-center gap-1.5">
                                        <CheckCircle2 className="size-4 text-primary" aria-hidden />
                                        {point}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <Card className="gap-0 overflow-hidden py-0">
                            <div className="flex items-center gap-2 border-b bg-muted px-4 py-2.5 text-xs font-medium text-muted-foreground">
                                <LayoutDashboard className="size-3.5" aria-hidden />
                                Course workspace — 6.470 Introduction to Systems Thinking
                            </div>
                            <CardContent className="space-y-1 p-3">
                                {[
                                    { label: 'Module 1 · Core Concepts', state: 'done' },
                                    { label: 'Module 2 · Feedback Loops', state: 'active' },
                                    { label: 'Module 3 · Modelling Systems', state: 'todo' },
                                    { label: 'Module 4 · Case Studies', state: 'todo' },
                                ].map((row) => (
                                    <div
                                        key={row.label}
                                        className="flex items-center justify-between rounded-sm px-3 py-2.5 text-sm"
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
                            </CardContent>
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
                        <Link
                            href="/courses"
                            className="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
                        >
                            View all <ArrowRight className="size-4" aria-hidden />
                        </Link>
                    </div>

                    <div className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {courses.map((course) => (
                            <CourseCard key={course.id} course={course} />
                        ))}
                    </div>
                </section>

                <section id="features" className="border-t border-border bg-card">
                    <div className="container-page grid gap-8 py-16 md:grid-cols-3">
                        {[
                            {
                                icon: LayoutDashboard,
                                title: 'Calm by default',
                                body: 'A restrained visual language inspired by MIT OpenCourseWare — generous whitespace, one clear accent colour, no dashboard noise.',
                            },
                            {
                                icon: Smartphone,
                                title: 'Built for a phone',
                                body: 'Every screen works on a small display first. The navigation collapses into a sheet, and actions stay reachable with a thumb.',
                            },
                            {
                                icon: ShieldCheck,
                                title: 'Own your data',
                                body: 'Self-host with a single compose file. Postgres for relational data, S3-compatible storage for uploads, and a one-click backup you can restore.',
                            },
                        ].map((item) => (
                            <div key={item.title}>
                                <span className="grid size-9 place-items-center rounded-md bg-accent text-accent-foreground">
                                    <item.icon className="size-4.5" aria-hidden />
                                </span>
                                <h3 className="mt-4 font-serif text-lg font-semibold text-foreground">
                                    {item.title}
                                </h3>
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
