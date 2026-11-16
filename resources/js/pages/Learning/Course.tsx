import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, Circle, Clock, FileText, MessageSquare, Video } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Progress } from '@/components/ui/progress';

type Lesson = {
    id: number;
    title: string;
    type: 'reading' | 'video' | 'quiz' | 'assignment' | 'forum';
    position: number;
    duration_minutes: number;
    body: string | null;
    is_preview: boolean;
    assignment?: { id: number } | null;
    quiz?: { id: number } | null;
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
    const allLessons = course.modules.flatMap((module) => module.lessons);
    const total = allLessons.length;
    const done = allLessons.filter((lesson) => completedLessonIds.includes(lesson.id)).length;
    const progress = total === 0 ? 0 : Math.round((done / total) * 100);

    return (
        <>
            <Head title={course.title} />

            <AppShell title={course.title}>
                <div className="grid gap-8 lg:grid-cols-[1fr_18rem]">
                    <div className="space-y-6">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                {course.instructor_name ?? 'LightLMS'}
                            </p>
                            <h2 className="mt-0.5 text-balance font-serif text-2xl font-semibold tracking-tight">
                                {course.title}
                            </h2>
                            <p className="mt-2 max-w-2xl text-muted-foreground">{course.summary}</p>
                        </div>

                        {course.modules.map((module) => (
                            <section key={module.id}>
                                <div className="mb-3 flex items-baseline justify-between gap-4">
                                    <h3 className="font-serif text-lg font-semibold text-foreground">
                                        {module.title}
                                    </h3>
                                    <span className="text-xs text-muted-foreground">
                                        {module.lessons.length}{' '}
                                        {module.lessons.length === 1 ? 'activity' : 'activities'}
                                    </span>
                                </div>

                                <Card className="gap-0 py-0">
                                    <ul className="divide-y">
                                        {module.lessons.map((lesson) => {
                                            const Icon = typeIcons[lesson.type] ?? FileText;
                                            const isDone = completedLessonIds.includes(lesson.id);

                                            return (
                                                <li
                                                    key={lesson.id}
                                                    className="flex flex-wrap items-center gap-3 px-5 py-3.5"
                                                >
                                                    <Icon
                                                        className="size-4 shrink-0 text-muted-foreground"
                                                        aria-hidden
                                                    />
                                                    <span className="min-w-0 flex-1">
                                                        <span className="block text-sm font-medium text-foreground">
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
                                                            {lesson.is_preview && (
                                                                <Badge variant="outline">Preview</Badge>
                                                            )}
                                                        </span>
                                                    </span>

                                                    {isDone ? (
                                                        <Badge variant="secondary" className="gap-1">
                                                            <CheckCircle2 className="size-3" aria-hidden />
                                                            Done
                                                        </Badge>
                                                    ) : lesson.type === 'assignment' && lesson.assignment ? (
                                                        <Button asChild size="sm">
                                                            <Link href={`/assignments/${lesson.assignment.id}`}>
                                                                Open assignment
                                                            </Link>
                                                        </Button>
                                                    ) : lesson.type === 'quiz' && lesson.quiz ? (
                                                        <Button asChild size="sm">
                                                            <Link href={`/quizzes/${lesson.quiz.id}`}>Open quiz</Link>
                                                        </Button>
                                                    ) : lesson.is_preview ? (
                                                        <Badge variant="outline">Preview</Badge>
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
                                </Card>
                            </section>
                        ))}
                    </div>

                    <aside className="lg:sticky lg:top-20 lg:self-start">
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

                        <Card className="mt-4">
                            <CardHeader>
                                <CardTitle className="text-sm">Contents</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="space-y-3">
                                    {course.modules.map((module) => (
                                        <li key={module.id}>
                                            <p className="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                                {module.title}
                                            </p>
                                            <ul className="mt-1.5 space-y-1">
                                                {module.lessons.map((lesson) => (
                                                    <li
                                                        key={lesson.id}
                                                        className="flex items-start gap-2 text-sm"
                                                    >
                                                        {completedLessonIds.includes(lesson.id) ? (
                                                            <CheckCircle2
                                                                className="mt-0.5 size-3.5 shrink-0 text-secondary"
                                                                aria-hidden
                                                            />
                                                        ) : (
                                                            <Circle
                                                                className="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                                                                aria-hidden
                                                            />
                                                        )}
                                                        <span className="min-w-0 text-muted-foreground">
                                                            {lesson.title}
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
            </AppShell>
        </>
    );
}
