import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Props = {
    course: {
        id: number;
        title: string;
        slug: string;
        summary: string;
        description: string | null;
        level: string;
        language: string;
        status: string;
        published_at: string | null;
        modules: {
            id: number;
            title: string;
            summary: string | null;
            lessons: {
                id: number;
                title: string;
                type: string;
                position: number;
                duration_minutes: number;
            }[];
        }[];
    };
    students: {
        id: number;
        name: string;
        email: string;
        pivot: { status: string; progress: number; enrolled_at: string | null };
    }[];
};

export default function TeachingCourse({ course, students }: Props) {
    const [sectionOpen, setSectionOpen] = useState(false);
    const [activityOpen, setActivityOpen] = useState<number | null>(null);

    const sectionForm = useForm({ title: '', summary: '' });
    const activityForm = useForm({
        module_id: 0,
        title: '',
        type: 'reading',
        body: '',
        duration_minutes: 0,
        is_preview: false,
    });

    return (
        <>
            <Head title={course.title} />

            <AppShell
                title={course.title}
                actions={
                    <div className="flex items-center gap-2">
                        <Badge
                            variant={course.status === 'published' ? 'secondary' : 'outline'}
                            className="capitalize"
                        >
                            {course.status}
                        </Badge>
                        <Button asChild size="sm" variant="outline">
                            <Link href={`/teaching/${course.slug}/roster`}>Roster</Link>
                        </Button>
                        <Button asChild size="sm" variant="outline">
                            <Link href={`/teaching/${course.slug}/gradebook`}>Gradebook</Link>
                        </Button>
                        <Button
                            size="sm"
                            variant={course.status === 'published' ? 'outline' : 'default'}
                            onClick={() =>
                                router.post(`/teaching/courses/${course.slug}/publish`, {}, { preserveScroll: true })
                            }
                        >
                            {course.status === 'published' ? 'Unpublish' : 'Publish'}
                        </Button>
                    </div>
                }
            >
                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Course content</CardTitle>
                                <CardDescription>
                                    Sections group the activities. Learners move through them in order.
                                </CardDescription>
                                <div className="pt-2">
                                    <Dialog open={sectionOpen} onOpenChange={setSectionOpen}>
                                        <DialogTrigger asChild>
                                            <Button size="sm">Add section</Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Add section</DialogTitle>
                                                <DialogDescription>
                                                    A section is a chapter of the course.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="grid gap-4">
                                                <div className="grid gap-2">
                                                    <Label htmlFor="section_title">Title</Label>
                                                    <Input
                                                        id="section_title"
                                                        value={sectionForm.data.title}
                                                        onChange={(event) =>
                                                            sectionForm.setData('title', event.target.value)
                                                        }
                                                    />
                                                    {sectionForm.errors.title && (
                                                        <p className="text-xs text-destructive">
                                                            {sectionForm.errors.title}
                                                        </p>
                                                    )}
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="section_summary">Summary</Label>
                                                    <Input
                                                        id="section_summary"
                                                        value={sectionForm.data.summary}
                                                        onChange={(event) =>
                                                            sectionForm.setData('summary', event.target.value)
                                                        }
                                                    />
                                                </div>
                                            </div>
                                            <DialogFooter>
                                                <Button variant="outline" onClick={() => setSectionOpen(false)}>
                                                    Cancel
                                                </Button>
                                                <Button
                                                    onClick={() =>
                                                        sectionForm.post(
                                                            `/teaching/courses/${course.slug}/modules`,
                                                            {
                                                                preserveScroll: true,
                                                                onSuccess: () => {
                                                                    sectionForm.reset();
                                                                    setSectionOpen(false);
                                                                },
                                                            },
                                                        )
                                                    }
                                                    disabled={sectionForm.processing}
                                                >
                                                    Add
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </CardHeader>

                            <CardContent>
                                {course.modules.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No sections yet. Add the first one to start building the course.
                                    </p>
                                ) : (
                                    <ul className="space-y-4">
                                        {course.modules.map((module) => (
                                            <li key={module.id} className="rounded-lg border p-4">
                                                <div className="flex flex-wrap items-center justify-between gap-2">
                                                    <div>
                                                        <p className="font-medium">{module.title}</p>
                                                        {module.summary && (
                                                            <p className="text-xs text-muted-foreground">
                                                                {module.summary}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        onClick={() => {
                                                            activityForm.setData('module_id', module.id);
                                                            setActivityOpen(module.id);
                                                        }}
                                                    >
                                                        Add activity
                                                    </Button>
                                                </div>

                                                {module.lessons.length > 0 && (
                                                    <ul className="mt-3 divide-y border-t">
                                                        {module.lessons.map((lesson) => (
                                                            <li
                                                                key={lesson.id}
                                                                className="flex items-center gap-3 py-2 text-sm"
                                                            >
                                                                <span className="min-w-0 flex-1 truncate">
                                                                    {lesson.position}. {lesson.title}
                                                                </span>
                                                                <Badge variant="outline" className="capitalize">
                                                                    {lesson.type}
                                                                </Badge>
                                                            </li>
                                                        ))}
                                                    </ul>
                                                )}

                                                <Dialog
                                                    open={activityOpen === module.id}
                                                    onOpenChange={(open) => !open && setActivityOpen(null)}
                                                >
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Add activity</DialogTitle>
                                                            <DialogDescription>
                                                                Activities are the individual pieces of
                                                                content inside a section.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <div className="grid gap-4">
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="lesson_title">Title</Label>
                                                                <Input
                                                                    id="lesson_title"
                                                                    value={activityForm.data.title}
                                                                    onChange={(event) =>
                                                                        activityForm.setData(
                                                                            'title',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                />
                                                                {activityForm.errors.title && (
                                                                    <p className="text-xs text-destructive">
                                                                        {activityForm.errors.title}
                                                                    </p>
                                                                )}
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="lesson_type">Type</Label>
                                                                <Select
                                                                    value={activityForm.data.type}
                                                                    onValueChange={(value) =>
                                                                        activityForm.setData('type', value)
                                                                    }
                                                                >
                                                                    <SelectTrigger id="lesson_type">
                                                                        <SelectValue />
                                                                    </SelectTrigger>
                                                                    <SelectContent>
                                                                        <SelectItem value="reading">
                                                                            Reading
                                                                        </SelectItem>
                                                                        <SelectItem value="video">Video</SelectItem>
                                                                        <SelectItem value="quiz">Quiz</SelectItem>
                                                                        <SelectItem value="assignment">
                                                                            Assignment
                                                                        </SelectItem>
                                                                        <SelectItem value="forum">Forum</SelectItem>
                                                                    </SelectContent>
                                                                </Select>
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="lesson_body">Content</Label>
                                                                <textarea
                                                                    id="lesson_body"
                                                                    rows={4}
                                                                    value={activityForm.data.body}
                                                                    onChange={(event) =>
                                                                        activityForm.setData(
                                                                            'body',
                                                                            event.target.value,
                                                                        )
                                                                    }
                                                                    className="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                                />
                                                            </div>
                                                            <div className="grid gap-2">
                                                                <Label htmlFor="lesson_duration">
                                                                    Duration (minutes)
                                                                </Label>
                                                                <Input
                                                                    id="lesson_duration"
                                                                    type="number"
                                                                    min={0}
                                                                    max={600}
                                                                    value={activityForm.data.duration_minutes}
                                                                    onChange={(event) =>
                                                                        activityForm.setData(
                                                                            'duration_minutes',
                                                                            Number(event.target.value),
                                                                        )
                                                                    }
                                                                />
                                                            </div>
                                                        </div>
                                                        <DialogFooter>
                                                            <Button
                                                                variant="outline"
                                                                onClick={() => setActivityOpen(null)}
                                                            >
                                                                Cancel
                                                            </Button>
                                                            <Button
                                                                onClick={() =>
                                                                    activityForm.post(
                                                                        `/teaching/courses/${course.slug}/lessons`,
                                                                        {
                                                                            preserveScroll: true,
                                                                            onSuccess: () => {
                                                                                activityForm.reset();
                                                                                setActivityOpen(null);
                                                                            },
                                                                        },
                                                                    )
                                                                }
                                                                disabled={activityForm.processing}
                                                            >
                                                                Add
                                                            </Button>
                                                        </DialogFooter>
                                                    </DialogContent>
                                                </Dialog>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    <aside className="space-y-4 lg:sticky lg:top-24 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Students</CardTitle>
                                <CardDescription>{students.length} enrolled</CardDescription>
                            </CardHeader>
                            <CardContent>
                                {students.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No students yet. Publish the course so learners can enrol.
                                    </p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Name</TableHead>
                                                <TableHead className="text-right">Progress</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {students.map((student) => (
                                                <TableRow key={student.id}>
                                                    <TableCell className="text-sm">{student.name}</TableCell>
                                                    <TableCell className="text-right text-sm">
                                                        {student.pivot.progress}%
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </AppShell>
        </>
    );
}
