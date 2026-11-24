import { Head, Link } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { EnrolledCourseListSkeleton } from '@/components/PageSkeletons';
import { useIsNavigatingAway } from '@/components/PendingVisits';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

type Props = {
    courses: {
        id: number;
        title: string;
        slug: string;
        summary: string;
        modules_count: number;
        pivot: { progress: number; status: string };
    }[];
};

export default function LearningIndex({ courses }: Props) {
    const isNavigating = useIsNavigatingAway();

    return (
        <>
            <Head title="My courses" />

            <AppShell title="My courses">
                {isNavigating ? (
                    <EnrolledCourseListSkeleton />
                ) : courses.length === 0 ? (
                    <Card>
                        <CardContent className="grid place-items-center py-16 text-center">
                            <p className="font-serif text-lg font-semibold">No courses yet</p>
                            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                Once you enrol in a course it shows up here with your progress.
                            </p>
                            <Link
                                href="/courses"
                                className="mt-4 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground"
                            >
                                Browse the catalogue
                            </Link>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {courses.map((course) => (
                            <Link
                                key={course.id}
                                href={`/learning/${course.slug}`}
                                className="flex flex-col gap-3 rounded-xl border bg-card p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h2 className="text-balance font-serif text-lg font-semibold leading-snug">
                                        {course.title}
                                    </h2>
                                    <Badge variant={course.pivot.status === 'completed' ? 'secondary' : 'outline'}>
                                        {course.pivot.status === 'completed' ? 'Done' : `${course.pivot.progress}%`}
                                    </Badge>
                                </div>
                                <p className="line-clamp-2 flex-1 text-sm text-muted-foreground">{course.summary}</p>
                                <div>
                                    <Progress value={course.pivot.progress} />
                                    <p className="mt-2 text-xs text-muted-foreground">
                                        {course.modules_count} sections
                                    </p>
                                </div>
                            </Link>
                        ))}
                    </div>
                )}
            </AppShell>
        </>
    );
}
