import { Head, Link } from '@inertiajs/react';
import { Clock, GraduationCap, Layers, User } from 'lucide-react';
import { useIsNavigatingAway } from '@/components/PendingVisits';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import type { Course, SharedProps } from '@/types';

type Props = {
    course: Course;
    auth: SharedProps['auth'];
};

const typeLabels: Record<string, string> = {
    video: 'Video',
    reading: 'Reading',
    quiz: 'Quiz',
    assignment: 'Assignment',
    forum: 'Forum',
};

export default function CourseShow({ course, auth }: Props) {
    const totalLessons = course.modules.reduce((sum, module) => sum + module.lessons.length, 0);
    const hours = Math.floor(course.duration_minutes / 60);
    const minutes = course.duration_minutes % 60;
    const isNavigating = useIsNavigatingAway();

    return (
        <>
            <Head title={course.title} />

            <SiteHeader user={auth.user} />

            <div className="bg-primary text-primary-foreground">
                <div className="container-page py-8">
                    <nav aria-label="Breadcrumb" className="text-sm text-primary-foreground/75">
                        <Link href="/" className="transition hover:text-primary-foreground">
                            Home
                        </Link>
                        <span className="px-1.5">/</span>
                        <Link href="/courses" className="transition hover:text-primary-foreground">
                            Courses
                        </Link>
                    </nav>

                    <h1 className="mt-3 text-balance font-serif text-3xl font-semibold leading-tight tracking-tight">
                        {course.title}
                    </h1>
                    <p className="mt-2 max-w-3xl text-primary-foreground/85">{course.summary}</p>

                    <div className="mt-4 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-primary-foreground/80">
                        <span className="inline-flex items-center gap-1.5">
                            <User className="size-4" aria-hidden />
                            {course.instructor_name ?? 'LightLMS'}
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <Layers className="size-4" aria-hidden />
                            {course.modules.length} modules
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <GraduationCap className="size-4" aria-hidden />
                            {totalLessons} activities
                        </span>
                        <span className="inline-flex items-center gap-1.5">
                            <Clock className="size-4" aria-hidden />
                            {hours > 0 ? `${hours}h ` : ''}
                            {minutes}m
                        </span>
                    </div>
                </div>
            </div>

            <main className="container-page py-10">
                <div className="grid gap-8 lg:grid-cols-[1fr_20rem]">
                    <article>
                        {course.description && (
                            <section>
                                <h2 className="font-serif text-xl font-semibold text-foreground">
                                    About this course
                                </h2>
                                <div className="mt-3 max-w-2xl leading-relaxed text-foreground/80">
                                    {course.description}
                                </div>
                            </section>
                        )}

                        <section className="mt-10">
                            <h2 className="font-serif text-xl font-semibold text-foreground">
                                Course outline
                            </h2>

                            <div className="mt-4 divide-y rounded-md border bg-card">
                                {isNavigating ? (
                                    <div className="space-y-3 p-5" aria-hidden>
                                        <Skeleton className="h-5 w-2/3" />
                                        <Skeleton className="h-4 w-1/2" />
                                        <Skeleton className="h-4 w-full" />
                                        <Skeleton className="h-4 w-5/6" />
                                    </div>
                                ) : (
                                    course.modules.map((module) => (
                                        <div key={module.id} className="p-5">
                                            <h3 className="font-medium text-foreground">{module.title}</h3>
                                            {module.summary && (
                                                <p className="mt-0.5 text-sm text-muted-foreground">
                                                    {module.summary}
                                                </p>
                                            )}

                                            <ul className="mt-3 divide-y border-t">
                                                {module.lessons.map((lesson) => (
                                                    <li
                                                        key={lesson.id}
                                                        className="flex items-center gap-3 py-2.5 text-sm"
                                                    >
                                                        <span className="w-6 shrink-0 text-muted-foreground">
                                                            {lesson.position}
                                                        </span>
                                                        <span className="min-w-0 flex-1 truncate text-foreground">
                                                            {lesson.title}
                                                        </span>
                                                        {lesson.duration_minutes > 0 && (
                                                            <span className="shrink-0 text-xs text-muted-foreground">
                                                                {lesson.duration_minutes} min
                                                            </span>
                                                        )}
                                                        <Badge
                                                            variant="outline"
                                                            className="shrink-0 capitalize"
                                                        >
                                                            {typeLabels[lesson.type] ?? lesson.type}
                                                        </Badge>
                                                    </li>
                                                ))}
                                            </ul>
                                        </div>
                                    ))
                                )}
                            </div>
                        </section>
                    </article>

                    <aside className="lg:sticky lg:top-20 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle as="h3" className="text-sm">Course info</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <InfoField label="Instructors" value={course.instructor_name ?? 'LightLMS'} />
                                <InfoField label="Level" value={course.level} capitalize />
                                <InfoField
                                    label="Learning resource types"
                                    value={[
                                        ...new Set(
                                            course.modules
                                                .flatMap((module) => module.lessons)
                                                .map((lesson) => typeLabels[lesson.type] ?? lesson.type),
                                        ),
                                    ].join(', ')}
                                />
                                <InfoField label="Total activities" value={String(totalLessons)} />
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </main>

            <SiteFooter />
        </>
    );
}

function InfoField({
    label,
    value,
    capitalize = false,
}: {
    label: string;
    value: string;
    capitalize?: boolean;
}) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                {label}
            </p>
            <p className={`mt-1 text-sm text-foreground ${capitalize ? 'capitalize' : ''}`}>{value}</p>
        </div>
    );
}
