import { Head, Link } from '@inertiajs/react';
import { ArrowRight, CalendarClock, GraduationCap, ListChecks } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

type Props = {
    enrollments: {
        id: number;
        title: string;
        slug: string;
        summary: string;
        enrollment_progress: number;
        enrollment_status: string;
        modules_count?: number;
    }[];
    upcoming: {
        title: string;
        slug: string;
        lesson_title: string;
        due_at: string | null;
    }[];
    grades: {
        id: number;
        score: string;
        max_score: string;
        graded_at: string | null;
        course: { title: string; slug: string } | null;
    }[];
    stats: {
        enrolled: number;
        completed: number;
        pendingSubmissions: number;
    };
};

export default function DashboardStudent({ stats, enrollments, upcoming, grades }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <AppShell title="Dashboard">
                <div className="grid gap-4 sm:grid-cols-3">
                    <StatCard
                        icon={GraduationCap}
                        label="Enrolled courses"
                        value={stats.enrolled}
                        href="/learning"
                    />
                    <StatCard
                        icon={ListChecks}
                        label="Completed"
                        value={stats.completed}
                        href="/learning"
                    />
                    <StatCard
                        icon={CalendarClock}
                        label="Awaiting review"
                        value={stats.pendingSubmissions}
                    />
                </div>

                <div className="mt-6 grid gap-6 lg:grid-cols-[1.4fr_1fr]">
                    <Card>
                        <CardHeader className="flex-row items-center justify-between gap-4">
                            <div>
                                <CardTitle className="text-base">My courses</CardTitle>
                                <CardDescription>Continue where you left off.</CardDescription>
                            </div>
                            <ButtonLink href="/learning">All courses</ButtonLink>
                        </CardHeader>
                        <CardContent>
                            {enrollments.length === 0 ? (
                                <EmptyState
                                    title="You are not enrolled yet"
                                    body="Browse the catalogue and enrol in a course to get started."
                                    href="/courses"
                                    cta="Browse the catalogue"
                                />
                            ) : (
                                <ul className="divide-y">
                                    {enrollments.map((course) => (
                                        <li key={course.id} className="py-4 first:pt-0 last:pb-0">
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="min-w-0">
                                                    <Link
                                                        href={`/learning/${course.slug}`}
                                                        className="font-medium text-foreground transition hover:text-primary"
                                                    >
                                                        {course.title}
                                                    </Link>
                                                    {course.summary && (
                                                        <p className="mt-0.5 line-clamp-1 text-sm text-muted-foreground">
                                                            {course.summary}
                                                        </p>
                                                    )}
                                                </div>
                                                <Badge
                                                    variant={
                                                        course.enrollment_status === 'completed'
                                                            ? 'secondary'
                                                            : 'outline'
                                                    }
                                                >
                                                    {course.enrollment_status === 'completed'
                                                        ? 'Completed'
                                                        : `${course.enrollment_progress}%`}
                                                </Badge>
                                            </div>
                                            <Progress value={course.enrollment_progress} className="mt-3" />
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Deadlines</CardTitle>
                            <CardDescription>Activities with a due date.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {upcoming.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nothing due in the near future.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {upcoming.map((item, index) => (
                                        <li key={index} className="flex items-start gap-3 py-3 first:pt-0 last:pb-0">
                                            <CalendarClock
                                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                aria-hidden
                                            />
                                            <div className="min-w-0">
                                                <p className="text-sm font-medium">{item.lesson_title}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {item.title} · due{' '}
                                                    {new Date(item.due_at!).toLocaleDateString()}
                                                </p>
                                            </div>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle className="text-base">Recent grades</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {grades.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No grades yet.</p>
                        ) : (
                            <ul className="divide-y">
                                {grades.map((grade) => (
                                    <li
                                        key={grade.id}
                                        className="flex items-center justify-between gap-4 py-3 text-sm first:pt-0 last:pb-0"
                                    >
                                        <span>{grade.course?.title ?? 'Course'}</span>
                                        <span className="font-medium tabular-nums">
                                            {grade.score} / {grade.max_score}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </AppShell>
        </>
    );
}

function StatCard({
    icon: Icon,
    label,
    value,
    href,
}: {
    icon: typeof GraduationCap;
    label: string;
    value: number;
    href?: string;
}) {
    const body = (
        <CardContent className="flex items-center gap-4">
            <span className="grid size-10 shrink-0 place-items-center rounded-md bg-accent text-accent-foreground">
                <Icon className="size-5" aria-hidden />
            </span>
            <div className="min-w-0">
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="font-serif text-2xl font-semibold leading-tight tabular-nums">{value}</p>
            </div>
        </CardContent>
    );

    if (href) {
        return (
            <Link href={href} className="transition hover:-translate-y-0.5 hover:shadow-[0_4px_10px_0_rgb(0_0_0/0.18)]">
                <Card>{body}</Card>
            </Link>
        );
    }

    return <Card>{body}</Card>;
}

function ButtonLink({ href, children }: { href: string; children: React.ReactNode }) {
    return (
        <Link
            href={href}
            className="inline-flex items-center gap-1 text-sm font-semibold text-primary hover:underline"
        >
            {children} <ArrowRight className="size-4" aria-hidden />
        </Link>
    );
}

function EmptyState({
    title,
    body,
    href,
    cta,
}: {
    title: string;
    body: string;
    href: string;
    cta: string;
}) {
    return (
        <div className="grid place-items-center py-10 text-center">
            <p className="font-serif text-base font-semibold text-foreground">{title}</p>
            <p className="mt-1 max-w-xs text-sm text-muted-foreground">{body}</p>
            <Link
                href={href}
                className="mt-4 rounded-sm bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
            >
                {cta}
            </Link>
        </div>
    );
}
