import { Head } from '@inertiajs/react';
import { SiteFooter, SiteHeader } from '@/components/SiteChrome';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
};

export default function CourseShow({ course, auth }: Props) {
    const totalLessons = course.modules.reduce((sum, module) => sum + module.lessons.length, 0);

    return (
        <>
            <Head title={course.title} />

            <SiteHeader user={auth.user} />

            <main className="container-page py-12">
                <div className="grid gap-10 lg:grid-cols-[1fr_20rem]">
                    <article>
                        <Badge variant="outline" className="capitalize">
                            {course.level}
                        </Badge>
                        <h1 className="mt-4 text-balance font-serif text-3xl font-semibold tracking-tight text-foreground">
                            {course.title}
                        </h1>
                        <p className="mt-3 text-lg text-muted-foreground">{course.summary}</p>

                        <div className="mt-5 flex flex-wrap items-center gap-x-5 gap-y-1 text-sm text-muted-foreground">
                            <span>{course.instructor_name ?? 'LightLMS'}</span>
                            <span>{course.modules.length} modules</span>
                            <span>{totalLessons} lessons</span>
                            <span>{Math.round(course.duration_minutes / 60)}h total</span>
                        </div>

                        {course.description && (
                            <div className="mt-10 max-w-2xl leading-relaxed text-muted-foreground">
                                {course.description}
                            </div>
                        )}
                    </article>

                    <aside className="lg:sticky lg:top-24 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Course outline</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="space-y-4">
                                    {course.modules.map((module) => (
                                        <li key={module.id}>
                                            <p className="text-sm font-medium text-foreground">{module.title}</p>
                                            <ul className="mt-1.5 space-y-1">
                                                {module.lessons.map((lesson) => (
                                                    <li
                                                        key={lesson.id}
                                                        className="flex items-baseline justify-between gap-3 text-sm text-muted-foreground"
                                                    >
                                                        <span className="truncate">
                                                            {lesson.position}. {lesson.title}
                                                        </span>
                                                        <span className="shrink-0 text-xs">
                                                            {typeLabels[lesson.type] ?? lesson.type}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        </li>
                                    ))}
                                </ol>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </main>

            <SiteFooter />
        </>
    );
}
