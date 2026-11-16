import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, FileText, Paperclip, Send } from 'lucide-react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type Attachment = {
    id: number;
    name: string;
    mime_type: string | null;
    size: number;
    url: string;
};

type Attempt = {
    id: number;
    content: string | null;
    status: string;
    score: number | null;
    feedback: string | null;
    submitted_at: string | null;
    graded_at: string | null;
    file: Attachment | null;
};

type Props = {
    course: { id: number; title: string; slug: string };
    assignment: {
        id: number;
        title: string;
        instructions: string | null;
        due_at: string | null;
        grade_max: number;
        submission_type: string;
        max_attempts: number;
        allow_late: boolean;
    };
    attemptsUsed: number;
    attemptsLeft: number;
    submissions: Attempt[];
    latest: Attempt | null;
};

function formatDate(value: string | null) {
    if (!value) {
        return null;
    }

    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}

function formatSize(bytes: number) {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function LearningAssignment({
    course,
    assignment,
    attemptsUsed,
    attemptsLeft,
    submissions,
    latest,
}: Props) {
    const form = useForm({ content: '', file: null as File | null });
    const dueAt = formatDate(assignment.due_at);
    const overdue = dueAt !== null && new Date(dueAt).getTime() < Date.now() && !assignment.allow_late;
    const exhausted = attemptsLeft <= 0;

    const submit = () => {
        form.post(`/assignments/${assignment.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    return (
        <>
            <Head title={assignment.title} />

            <AppShell title={assignment.title}>
                <div className="mx-auto max-w-3xl space-y-6">
                    <Link
                        href={`/learning/${course.slug}`}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ArrowLeft className="size-3.5" aria-hidden />
                        Back to {course.title}
                    </Link>

                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <Badge variant="outline">Assignment</Badge>
                            {latest?.status === 'graded' && <Badge variant="secondary">Graded</Badge>}
                            {latest && latest.status !== 'graded' && <Badge variant="outline">Awaiting review</Badge>}
                        </div>
                        <h2 className="mt-2 text-balance font-serif text-2xl font-semibold tracking-tight">
                            {assignment.title}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Worth {assignment.grade_max} points
                            {dueAt && (
                                <>
                                    {' · due '}
                                    <span className="inline-flex items-center gap-1">
                                        <Clock className="size-3" aria-hidden />
                                        {dueAt}
                                    </span>
                                </>
                            )}
                        </p>
                    </div>

                    {assignment.instructions && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Instructions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                    {assignment.instructions}
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {overdue && (
                        <Card className="border-destructive/40">
                            <CardContent className="py-4 text-sm text-destructive">
                                The deadline has passed and late submissions are not accepted for this
                                assignment.
                            </CardContent>
                        </Card>
                    )}

                    {latest?.status === 'graded' && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Your mark</CardTitle>
                                <CardDescription>
                                    {latest.score === null
                                        ? 'Graded without a score.'
                                        : `${latest.score} of ${assignment.grade_max} points`}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {latest.feedback && (
                                    <p className="whitespace-pre-line text-sm leading-relaxed">
                                        {latest.feedback}
                                    </p>
                                )}
                                {latest.file && (
                                    <a
                                        href={latest.file.url}
                                        className="inline-flex items-center gap-2 text-sm text-primary underline-offset-4 hover:underline"
                                    >
                                        <Paperclip className="size-3.5" aria-hidden />
                                        {latest.file.name}
                                        <span className="text-xs text-muted-foreground">
                                            ({formatSize(latest.file.size)})
                                        </span>
                                    </a>
                                )}
                                <p className="text-xs text-muted-foreground">
                                    Graded {formatDate(latest.graded_at) ?? 'recently'}
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">Your answer</CardTitle>
                            <CardDescription>
                                {exhausted
                                    ? 'You have used every attempt for this assignment.'
                                    : `Attempt ${attemptsUsed + 1} of ${assignment.max_attempts}.`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-2">
                                <Label htmlFor="content">Response</Label>
                                <Textarea
                                    id="content"
                                    rows={10}
                                    value={form.data.content}
                                    onChange={(event) => form.setData('content', event.target.value)}
                                    placeholder="Write your answer here. Minimum 10 characters."
                                    disabled={exhausted || overdue}
                                    aria-invalid={Boolean(form.errors.content)}
                                />
                                {form.errors.content && (
                                    <p className="text-xs text-destructive">{form.errors.content}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="file">Attachment</Label>
                                <Input
                                    id="file"
                                    type="file"
                                    onChange={(event) =>
                                        form.setData('file', event.target.files?.[0] ?? null)
                                    }
                                    disabled={exhausted || overdue}
                                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.txt,.md,.csv,.jpg,.jpeg,.png,.gif,.webp"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Optional. Up to 10 MB. PDF, Word, spreadsheet, slide, zip, text or
                                    image.
                                </p>
                                {form.errors.file && (
                                    <p className="text-xs text-destructive">{form.errors.file}</p>
                                )}
                            </div>
                            <div className="mt-4 flex items-center gap-3">
                                <Button onClick={submit} disabled={form.processing || exhausted || overdue}>
                                    <Send className="size-4" aria-hidden />
                                    Submit answer
                                </Button>
                                {exhausted && (
                                    <span className="text-xs text-muted-foreground">
                                        No attempts left.
                                    </span>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {submissions.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Your attempts</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y">
                                    {submissions.map((attempt, index) => (
                                        <li key={attempt.id} className="flex items-start gap-3 py-3">
                                            <FileText
                                                className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                aria-hidden
                                            />
                                            <div className="min-w-0 flex-1">
                                                <p className="text-sm font-medium">
                                                    Attempt {index + 1}
                                                    {attempt.id === latest?.id && (
                                                        <span className="ml-2 text-xs font-normal text-muted-foreground">
                                                            latest
                                                        </span>
                                                    )}
                                                </p>
                                                <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                                                    {attempt.content}
                                                </p>
                                                <p className="mt-0.5 text-xs text-muted-foreground">
                                                    Submitted {formatDate(attempt.submitted_at)}
                                                    {attempt.score !== null &&
                                                        ` · scored ${attempt.score}/${assignment.grade_max}`}
                                                </p>
                                                {attempt.file && (
                                                    <a
                                                        href={attempt.file.url}
                                                        className="mt-1 inline-flex items-center gap-1.5 text-xs text-primary underline-offset-4 hover:underline"
                                                    >
                                                        <Paperclip className="size-3" aria-hidden />
                                                        {attempt.file.name}
                                                    </a>
                                                )}
                                            </div>
                                            <Badge
                                                variant={attempt.status === 'graded' ? 'secondary' : 'outline'}
                                            >
                                                {attempt.status === 'graded' ? 'Graded' : 'Submitted'}
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </AppShell>
        </>
    );
}
