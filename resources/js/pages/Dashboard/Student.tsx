import { Head } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    stats: {
        enrolled: number;
        completed: number;
        pendingSubmissions: number;
    };
    enrollments: {
        id: number;
        title: string;
        slug: string;
        progress: number;
        status: string;
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
};

export default function DashboardStudent({ stats, enrollments, upcoming, grades }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <AppShell title="Dashboard">
                <div className="grid gap-6 lg:grid-cols-3">
                    <StatCard label="Enrolled courses" value={stats.enrolled} />
                    <StatCard label="Completed" value={stats.completed} />
                    <StatCard label="Awaiting review" value={stats.pendingSubmissions} />
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>My courses</CardTitle>
                            <CardDescription>Continue where you left off.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {enrollments.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    You are not enrolled in any course yet.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {enrollments.map((course) => (
                                        <li key={course.id} className="flex items-center justify-between gap-4 py-3">
                                            <span className="text-sm font-medium">{course.title}</span>
                                            <Badge variant={course.status === 'completed' ? 'secondary' : 'outline'}>
                                                {course.progress}%
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Deadlines</CardTitle>
                            <CardDescription>Assignments and activities with a due date.</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {upcoming.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Nothing due in the near future.</p>
                            ) : (
                                <ul className="divide-y">
                                    {upcoming.map((item, index) => (
                                        <li key={index} className="py-3">
                                            <p className="text-sm font-medium">{item.lesson_title}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.title} · due {new Date(item.due_at!).toLocaleDateString()}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Recent grades</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {grades.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No grades yet.</p>
                        ) : (
                            <ul className="divide-y">
                                {grades.map((grade) => (
                                    <li
                                        key={grade.id}
                                        className="flex items-center justify-between gap-4 py-3 text-sm"
                                    >
                                        <span>{grade.course?.title ?? 'Course'}</span>
                                        <span className="font-medium">
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
