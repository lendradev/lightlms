import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, Clock, Save } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

type HandIn = {
    id: number;
    student: { id: number; name: string; email: string };
    content: string | null;
    status: string;
    score: number | null;
    feedback: string | null;
    submitted_at: string | null;
    graded_at: string | null;
    grader: string | null;
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
    };
    submissions: HandIn[];
    stats: { submitted: number; graded: number; awaiting: number };
    highlight: number | null;
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

export default function TeachingAssignment({
    course,
    assignment,
    submissions,
    stats,
    highlight,
}: Props) {
    const [selected, setSelected] = useState<number | null>(highlight ?? submissions[0]?.id ?? null);
    const [scores, setScores] = useState<Record<number, string>>({});
    const [feedback, setFeedback] = useState<Record<number, string>>({});
    const [saving, setSaving] = useState(false);

    const current = submissions.find((submission) => submission.id === selected) ?? null;

    const grade = (submissionId: number) => {
        const raw = scores[submissionId] ?? '';
        const value = raw === '' ? null : Number(raw);

        setSaving(true);

        router.post(
            `/teaching/${course.slug}/assignments/${assignment.id}/${submissionId}/grade`,
            {
                score: value,
                feedback: feedback[submissionId] ?? '',
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <>
            <Head title={`${assignment.title} — ${course.title}`} />

            <AppShell
                title={assignment.title}
                actions={
                    <Button asChild size="sm" variant="outline">
                        <Link href={`/teaching/${course.slug}/gradebook`}>Gradebook</Link>
                    </Button>
                }
            >
                <div className="space-y-6">
                    <Link
                        href={`/teaching/${course.slug}`}
                        className="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition hover:text-foreground focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                    >
                        <ArrowLeft className="size-3.5" aria-hidden />
                        Back to {course.title}
                    </Link>

                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <p className="text-sm text-muted-foreground">{course.title}</p>
                            <h2 className="mt-0.5 text-balance font-serif text-2xl font-semibold tracking-tight">
                                {assignment.title}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                Worth {assignment.grade_max} points
                                {assignment.due_at && ` · due ${formatDate(assignment.due_at)}`}
                            </p>
                        </div>

                        <dl className="flex gap-6 text-sm">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    Hand-ins
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.submitted}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    Graded
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.graded}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    Awaiting
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.awaiting}</dd>
                            </div>
                        </dl>
                    </div>

                    {assignment.instructions && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Instructions given to learners</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                    {assignment.instructions}
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    <div className="grid gap-6 lg:grid-cols-[20rem_1fr]">
                        <Card className="h-fit">
                            <CardHeader>
                                <CardTitle className="text-sm">Learners</CardTitle>
                                <CardDescription>
                                    {submissions.length === 0
                                        ? 'Nothing handed in yet.'
                                        : 'Select a hand-in to grade.'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {submissions.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No learner has submitted this assignment yet.
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {submissions.map((submission) => (
                                            <li key={submission.id}>
                                                <button
                                                    type="button"
                                                    onClick={() => setSelected(submission.id)}
                                                    className={`flex w-full items-start justify-between gap-2 py-2.5 text-left transition focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring ${
                                                        selected === submission.id
                                                            ? 'text-foreground'
                                                            : 'text-muted-foreground hover:text-foreground'
                                                    }`}
                                                    aria-current={selected === submission.id}
                                                >
                                                    <span className="min-w-0">
                                                        <span className="block truncate text-sm font-medium">
                                                            {submission.student.name}
                                                        </span>
                                                        <span className="block text-xs">
                                                            {formatDate(submission.submitted_at)}
                                                        </span>
                                                    </span>
                                                    {submission.status === 'graded' ? (
                                                        <CheckCircle2
                                                            className="mt-0.5 size-4 shrink-0 text-secondary"
                                                            aria-label="Graded"
                                                        />
                                                    ) : (
                                                        <Badge variant="outline">Awaiting</Badge>
                                                    )}
                                                </button>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <div className="space-y-6">
                            {current === null ? (
                                <Card>
                                    <CardContent className="grid place-items-center py-16 text-center">
                                        <p className="font-serif text-lg font-semibold">No hand-in selected</p>
                                        <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                                            Choose a learner on the left to read and grade their answer.
                                        </p>
                                    </CardContent>
                                </Card>
                            ) : (
                                <>
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="text-sm">
                                                {current.student.name}
                                            </CardTitle>
                                            <CardDescription>
                                                {current.student.email} · submitted{' '}
                                                {formatDate(current.submitted_at)}
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent>
                                            <p className="whitespace-pre-line text-sm leading-relaxed">
                                                {current.content}
                                            </p>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="text-sm">Grade</CardTitle>
                                            <CardDescription>
                                                Saving mirrors the mark into the course gradebook.
                                            </CardDescription>
                                        </CardHeader>
                                        <CardContent className="space-y-4">
                                            <div className="grid gap-2">
                                                <Label htmlFor="score">Score (out of {assignment.grade_max})</Label>
                                                <Input
                                                    id="score"
                                                    type="number"
                                                    step="0.01"
                                                    min={0}
                                                    max={assignment.grade_max}
                                                    value={
                                                        scores[current.id] ??
                                                        (current.score === null ? '' : String(current.score))
                                                    }
                                                    onChange={(event) =>
                                                        setScores((previous) => ({
                                                            ...previous,
                                                            [current.id]: event.target.value,
                                                        }))
                                                    }
                                                    className="w-32 tabular-nums"
                                                />
                                            </div>

                                            <div className="grid gap-2">
                                                <Label htmlFor="feedback">Feedback</Label>
                                                <Textarea
                                                    id="feedback"
                                                    rows={5}
                                                    value={
                                                        feedback[current.id] ??
                                                        (current.feedback ?? '')
                                                    }
                                                    onChange={(event) =>
                                                        setFeedback((previous) => ({
                                                            ...previous,
                                                            [current.id]: event.target.value,
                                                        }))
                                                    }
                                                    placeholder="What went well, and what to improve."
                                                />
                                            </div>

                                            <Button
                                                onClick={() => grade(current.id)}
                                                disabled={saving}
                                            >
                                                <Save className="size-4" aria-hidden />
                                                Save grade
                                            </Button>

                                            {current.graded_at && (
                                                <p className="flex items-center gap-1.5 text-xs text-muted-foreground">
                                                    <Clock className="size-3" aria-hidden />
                                                    Last graded {formatDate(current.graded_at)}
                                                    {current.grader && ` by ${current.grader}`}
                                                </p>
                                            )}
                                        </CardContent>
                                    </Card>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </AppShell>
        </>
    );
}
