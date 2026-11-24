import { Head } from '@inertiajs/react';
import { AppShell } from '@/components/AppShell';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';

type Props = {
    stats: {
        users: number;
        courses: number;
        publishedCourses: number;
        enrollments: number;
    };
    siteName: string;
};

export default function DashboardAdmin({ stats, siteName }: Props) {
    return (
        <>
            <Head title="Administration" />

            <AppShell title="Administration">
                <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard label="Users" value={stats.users} />
                    <StatCard label="Courses" value={stats.courses} />
                    <StatCard label="Published" value={stats.publishedCourses} />
                    <StatCard label="Enrollments" value={stats.enrollments} />
                </div>

                <Card className="mt-8">
                    <CardHeader>
                        <CardTitle>{siteName}</CardTitle>
                        <CardDescription>
                            Use the sidebar to manage users, invitations and site settings.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            This screen reports what the site holds right now: users, courses and
                            enrollments. Backups and restores are in the sidebar under Backup.
                        </p>
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
