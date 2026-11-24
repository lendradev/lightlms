import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { EnrolledCourseListSkeleton } from '@/components/PageSkeletons';
import { useIsNavigatingAway } from '@/components/PendingVisits';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';

type Props = {
    courses: {
        id: number;
        title: string;
        slug: string;
        status: string;
        modules_count: number;
        students_count: number;
    }[];
};

export default function TeachingIndex({ courses }: Props) {
    const [creating, setCreating] = useState(false);
    const isNavigating = useIsNavigatingAway();

    const { data, setData, post, processing, errors, reset } = useForm({
        title: '',
        summary: '',
        description: '',
        level: 'beginner',
        language: 'en',
    });

    return (
        <>
            <Head title="Teaching" />

            <AppShell
                title="Teaching"
                actions={
                    <Dialog open={creating} onOpenChange={setCreating}>
                        <DialogTrigger asChild>
                            <Button size="sm">New course</Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>New course</DialogTitle>
                                <DialogDescription>
                                    Courses start as a draft. Publish when the content is ready.
                                </DialogDescription>
                            </DialogHeader>
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="title">Title</Label>
                                    <Input
                                        id="title"
                                        value={data.title}
                                        onChange={(event) => setData('title', event.target.value)}
                                    />
                                    {errors.title && (
                                        <p className="text-xs text-destructive">{errors.title}</p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="summary">Summary</Label>
                                    <Textarea
                                        id="summary"
                                        rows={2}
                                        value={data.summary}
                                        onChange={(event) => setData('summary', event.target.value)}
                                    />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="level">Level</Label>
                                        <Select
                                            value={data.level}
                                            onValueChange={(value) => setData('level', value)}
                                        >
                                            <SelectTrigger id="level">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="beginner">Beginner</SelectItem>
                                                <SelectItem value="intermediate">Intermediate</SelectItem>
                                                <SelectItem value="advanced">Advanced</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="language">Language</Label>
                                        <Input
                                            id="language"
                                            value={data.language}
                                            onChange={(event) => setData('language', event.target.value)}
                                        />
                                    </div>
                                </div>
                            </div>
                            <DialogFooter>
                                <Button variant="outline" onClick={() => setCreating(false)}>
                                    Cancel
                                </Button>
                                <Button
                                    onClick={() =>
                                        post('/teaching/courses', {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                reset();
                                                setCreating(false);
                                            },
                                        })
                                    }
                                    disabled={processing}
                                >
                                    Create draft
                                </Button>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                }
            >
                {isNavigating ? (
                    <EnrolledCourseListSkeleton />
                ) : courses.length === 0 ? (
                    <Card>
                        <CardContent className="grid place-items-center py-16 text-center">
                            <p className="font-serif text-lg font-semibold">No courses yet</p>
                            <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                Create your first course, add sections and activities, then publish it.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {courses.map((course) => (
                            <Link
                                key={course.id}
                                href={`/teaching/${course.slug}`}
                                className="flex flex-col gap-3 rounded-xl border bg-card p-5 transition hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <h2 className="text-balance font-serif text-lg font-semibold leading-snug">
                                        {course.title}
                                    </h2>
                                    <Badge
                                        variant={
                                            course.status === 'published' ? 'secondary' : 'outline'
                                        }
                                        className="capitalize"
                                    >
                                        {course.status}
                                    </Badge>
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    {course.modules_count} sections · {course.students_count} students
                                </p>
                            </Link>
                        ))}
                    </div>
                )}
            </AppShell>
        </>
    );
}
