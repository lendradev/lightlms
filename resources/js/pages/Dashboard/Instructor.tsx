import { Head } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { StatCardSkeleton } from '@/components/PageSkeletons';
import { useIsNavigatingAway } from '@/components/PendingVisits';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    courses: {
        id: number;
        title: string;
        slug: string;
        status: string;
        modules_count: number;
    }[];
    stats: {
        courses: number;
        students: number;
        pendingReviews: number;
    };
};

export default function DashboardInstructor({ courses, stats }: Props) {
    const isNavigating = useIsNavigatingAway();

    return (
        <>
            <Head title="Teaching" />

            <AppShell title="Teaching">
                {isNavigating ? (
                    <div className="grid gap-6 lg:grid-cols-3">
                        <StatCardSkeleton />
                        <StatCardSkeleton />
                        <StatCardSkeleton />
                    </div>
                ) : (
                    <div className="grid gap-6 lg:grid-cols-3">
                        <StatCard label="Courses" value={stats.courses} />
                        <StatCard label="Enrolled students" value={stats.students} />
                        <StatCard label="Awaiting review" value={stats.pendingReviews} />
                    </div>
                )}

                <Card className="mt-8">
                    <CardHeader>
                        <CardTitle>My courses</CardTitle>
                        <CardDescription>Courses you own and manage.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {courses.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                You have not created any course yet.
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {courses.map((course) => (
                                    <li key={course.id} className="flex items-center justify-between gap-4 py-3">
                                        <span className="text-sm font-medium">{course.title}</span>
                                        <div className="flex items-center gap-2">
                                            <span className="text-xs text-muted-foreground">
                                                {course.modules_count} modules
                                            </span>
                                            <Badge variant="outline" className="capitalize">
                                                {course.status}
                                            </Badge>
                                        </div>
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

function StatCard({ label, value }: { label: string; value: number }) {
    return (
        <Card>
            <CardContent>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1 font-serif text-3xl font-semibold tracking-tight">{value}</p>
            </CardContent>
        </Card>
    );
}
