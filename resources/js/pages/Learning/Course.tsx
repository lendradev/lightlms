import { Head, router } from '@inertiajs/react';
import { CheckCircle2, Circle, Clock, FileText, MessageSquare, PlayCircle, Video } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

type Lesson = {
    id: number;
    title: string;
    type: 'reading' | 'video' | 'quiz' | 'assignment' | 'forum';
    position: number;
    duration_minutes: number;
    body: string | null;
    is_preview: boolean;
};

type Module = {
    id: number;
    title: string;
    summary: string | null;
    lessons: Lesson[];
};

type Props = {
    course: {
        id: number;
        title: string;
        summary: string;
        instructor_name: string | null;
        modules: Module[];
    };
    completedLessonIds: number[];
};

const typeIcons: Record<string, typeof FileText> = {
    reading: FileText,
    video: Video,
    quiz: FileText,
    assignment: FileText,
    forum: MessageSquare,
};

export default function LearningCourse({ course, completedLessonIds }: Props) {
    const total = course.modules.reduce((sum, module) => sum + module.lessons.length, 0);
    const done = course.modules
        .flatMap((module) => module.lessons)
        .filter((lesson) => completedLessonIds.includes(lesson.id)).length;
    const progress = total === 0 ? 0 : Math.round((done / total) * 100);

    return (
        <>
            <Head title={course.title} />

            <AppShell title={course.title}>
                <div className="grid gap-6 lg:grid-cols-[1fr_20rem]">
                    <div className="space-y-6">
                        <div>
                            <h2 className="text-balance font-serif text-2xl font-semibold tracking-tight">
                                {course.title}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {course.instructor_name ?? 'LightLMS'} · {total} activities
                            </p>
                        </div>

                        {course.modules.map((module) => (
                            <Card key={module.id}>
                                <CardHeader>
                                    <CardTitle className="text-base">{module.title}</CardTitle>
                                    {module.summary && (
                                        <CardDescription>{module.summary}</CardDescription>
                                    )}
                                </CardHeader>
                                <CardContent>
                                    <ul className="divide-y">
                                        {module.lessons.map((lesson) => {
                                            const Icon = typeIcons[lesson.type] ?? PlayCircle;
                                            const isDone = completedLessonIds.includes(lesson.id);

                                            return (
                                                <li
                                                    key={lesson.id}
                                                    className="flex flex-wrap items-center gap-3 py-3"
                                                >
                                                    <Icon className="size-4 shrink-0 text-muted-foreground" aria-hidden />
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block text-sm font-medium">
                                                            {lesson.position}. {lesson.title}
                                                        </span>
                                                        <span className="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                                            <span className="capitalize">{lesson.type}</span>
                                                            {lesson.duration_minutes > 0 && (
                                                                <span className="inline-flex items-center gap-1">
                                                                    <Clock className="size-3" aria-hidden />
                                                                    {lesson.duration_minutes} min
                                                                </span>
                                                            )}
                                                            {lesson.is_preview && <Badge variant="outline">Preview</Badge>}
                                                        </span>
                                                    </span>

                                                    {isDone ? (
                                                        <Badge variant="secondary" className="gap-1">
                                                            <CheckCircle2 className="size-3" aria-hidden />
                                                            Done
                                                        </Badge>
                                                    ) : (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            onClick={() =>
                                                                router.post(
                                                                    `/learning/lessons/${lesson.id}/complete`,
                                                                    {},
                                                                    { preserveScroll: true },
                                                                )
                                                            }
                                                        >
                                                            <Circle className="size-3" aria-hidden />
                                                            Mark complete
                                                        </Button>
                                                    )}
                                                </li>
                                            );
                                        })}
                                    </ul>
                                </CardContent>
                            </Card>
                        ))}
                    </div>

                    <aside className="lg:sticky lg:top-24 lg:self-start">
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Your progress</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Progress value={progress} />
                                <p className="mt-2 text-xs text-muted-foreground">
                                    {done} of {total} activities complete ({progress}%)
                                </p>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </AppShell>
        </>
    );
}
