import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Plus, Save, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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

type Question = {
    id: number;
    type: string;
    prompt: string;
    options: string[];
    answer: string[] | boolean[];
    points: number;
    explanation: string | null;
};

type Attempt = {
    id: number;
    student: { id: number; name: string; email: string };
    score: number;
    max_score: number;
    percent: number | null;
    submitted_at: string | null;
    pending: boolean;
    answers: Record<string, { given: unknown; correct: boolean | null; awarded: number | null }>;
};

type Props = {
    course: { id: number; title: string; slug: string };
    quiz: {
        id: number;
        title: string;
        max_attempts: number;
        pass_mark: number | null;
        time_limit_minutes: number | null;
        question_count: number;
        total_points: number;
    };
    questions: Question[];
    attempts: Attempt[];
    stats: { attempts: number; pending: number; average: number | null };
};

const TYPES = [
    { value: 'multiple_choice', label: 'Multiple choice' },
    { value: 'multiple_select', label: 'Multiple select' },
    { value: 'true_false', label: 'True or false' },
    { value: 'short_answer', label: 'Short answer' },
    { value: 'essay', label: 'Essay' },
];

export default function TeachingQuiz({ course, quiz, questions, attempts, stats }: Props) {
    const [type, setType] = useState('multiple_choice');
    const [prompt, setPrompt] = useState('');
    const [options, setOptions] = useState(['', '', '', '']);
    const [answer, setAnswer] = useState('');
    const [points, setPoints] = useState('1');
    const [saving, setSaving] = useState(false);

    const [marks, setMarks] = useState<Record<string, string>>({});
    const [feedback, setFeedback] = useState<Record<string, string>>({});

    const needsOptions = type === 'multiple_choice' || type === 'multiple_select';

    const addQuestion = () => {
        setSaving(true);

        router.post(
            `/teaching/${course.slug}/quizzes/${quiz.id}/questions`,
            {
                type,
                prompt,
                options: needsOptions ? options.filter((option) => option.trim() !== '') : [],
                answer: type === 'true_false' ? answer === 'true' : answer,
                points: Number(points),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSaving(false);
                    setPrompt('');
                    setAnswer('');
                    setOptions(['', '', '', '']);
                },
            },
        );
    };

    const removeQuestion = (questionId: number) => {
        router.delete(`/teaching/${course.slug}/quizzes/${quiz.id}/questions/${questionId}`, {
            preserveScroll: true,
        });
    };

    const mark = (attempt: Attempt, question: Question) => {
        const key = `${attempt.id}-${question.id}`;
        const raw = marks[key] ?? '';
        const value = raw === '' ? null : Number(raw);

        setSaving(true);

        router.post(
            `/teaching/${course.slug}/quizzes/${quiz.id}/attempts/${attempt.id}/mark`,
            {
                question_id: question.id,
                points: value,
                feedback: feedback[key] ?? '',
            },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    return (
        <>
            <Head title={`${quiz.title} — ${course.title}`} />

            <AppShell
                title={quiz.title}
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
                                {quiz.title}
                            </h2>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {quiz.question_count} questions · {quiz.total_points} points ·{' '}
                                {quiz.max_attempts} attempts allowed
                                {quiz.pass_mark !== null && ` · pass mark ${quiz.pass_mark}%`}
                            </p>
                        </div>

                        <dl className="flex gap-6 text-sm">
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    Attempts
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.attempts}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    To mark
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.pending}</dd>
                            </div>
                            <div>
                                <dt className="text-xs uppercase tracking-wide text-muted-foreground">
                                    Avg score
                                </dt>
                                <dd className="font-medium tabular-nums">{stats.average ?? '—'}</dd>
                            </div>
                        </dl>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        <Card>
                            <CardHeader>
                                <CardTitle as="h3" className="text-sm">Question bank</CardTitle>
                                <CardDescription>
                                    Answers stay on the server and are never sent to a learner.
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {questions.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No questions yet. Add the first one on the right.
                                    </p>
                                ) : (
                                    <ul className="divide-y">
                                        {questions.map((question, index) => (
                                            <li key={question.id} className="py-3">
                                                <div className="flex items-start justify-between gap-3">
                                                    <div className="min-w-0">
                                                        <p className="text-sm font-medium">
                                                            {index + 1}. {question.prompt}
                                                        </p>
                                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                                            {TYPES.find((item) => item.value === question.type)?.label}{' '}
                                                            · {question.points}{' '}
                                                            {question.points === 1 ? 'point' : 'points'}
                                                        </p>
                                                        {question.options.length > 0 && (
                                                            <p className="mt-1 text-xs text-muted-foreground">
                                                                Options: {question.options.join(' | ')}
                                                            </p>
                                                        )}
                                                    </div>
                                                    <Button
                                                        size="icon"
                                                        variant="ghost"
                                                        onClick={() => removeQuestion(question.id)}
                                                        aria-label={`Remove question ${index + 1}`}
                                                    >
                                                        <Trash2 className="size-4" aria-hidden />
                                                    </Button>
                                                </div>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </CardContent>
                        </Card>

                        <Card className="h-fit">
                            <CardHeader>
                                <CardTitle as="h3" className="text-sm">Add a question</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="question_type">Type</Label>
                                    <Select value={type} onValueChange={setType}>
                                        <SelectTrigger id="question_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {TYPES.map((item) => (
                                                <SelectItem key={item.value} value={item.value}>
                                                    {item.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="question_prompt">Prompt</Label>
                                    <Textarea
                                        id="question_prompt"
                                        rows={3}
                                        value={prompt}
                                        onChange={(event) => setPrompt(event.target.value)}
                                        placeholder="What do you want to ask?"
                                    />
                                </div>

                                {needsOptions && (
                                    <div className="grid gap-2">
                                        <Label>Options</Label>
                                        {options.map((option, index) => (
                                            <Input
                                                key={index}
                                                value={option}
                                                onChange={(event) =>
                                                    setOptions((previous) =>
                                                        previous.map((item, itemIndex) =>
                                                            itemIndex === index ? event.target.value : item,
                                                        ),
                                                    )
                                                }
                                                placeholder={`Option ${index + 1}`}
                                                aria-label={`Option ${index + 1}`}
                                            />
                                        ))}
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="question_answer">
                                        {type === 'multiple_select'
                                            ? 'Correct options (comma separated)'
                                            : type === 'essay'
                                              ? 'Answer'
                                              : 'Correct answer'}
                                    </Label>
                                    {type === 'true_false' ? (
                                        <Select value={answer} onValueChange={setAnswer}>
                                            <SelectTrigger id="question_answer">
                                                <SelectValue placeholder="Choose one" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="true">True</SelectItem>
                                                <SelectItem value="false">False</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    ) : (
                                        <Input
                                            id="question_answer"
                                            value={answer}
                                            onChange={(event) => setAnswer(event.target.value)}
                                            placeholder={
                                                type === 'multiple_select'
                                                    ? 'Option 1, Option 2'
                                                    : type === 'essay'
                                                      ? 'Handled by a marker'
                                                      : 'Option 1'
                                            }
                                        />
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="question_points">Points</Label>
                                    <Input
                                        id="question_points"
                                        type="number"
                                        min={0.5}
                                        max={100}
                                        step={0.5}
                                        value={points}
                                        onChange={(event) => setPoints(event.target.value)}
                                        className="w-28"
                                    />
                                </div>

                                <Button onClick={addQuestion} disabled={saving || prompt.trim() === ''}>
                                    <Plus className="size-4" aria-hidden />
                                    Add question
                                </Button>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle as="h3" className="text-sm">Attempts</CardTitle>
                            <CardDescription>
                                Short answers and essays are marked here by hand.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {attempts.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    Nobody has attempted this quiz yet.
                                </p>
                            ) : (
                                <ul className="divide-y">
                                    {attempts.map((attempt) => (
                                        <li key={attempt.id} className="py-4">
                                            <div className="flex flex-wrap items-center justify-between gap-2">
                                                <span className="text-sm font-medium">
                                                    {attempt.student.name}
                                                </span>
                                                <span className="flex items-center gap-2">
                                                    {attempt.pending && <Badge variant="outline">To mark</Badge>}
                                                    <span className="text-sm tabular-nums">
                                                        {attempt.score}/{attempt.max_score}
                                                        {attempt.percent !== null && (
                                                            <span className="ml-1 text-xs text-muted-foreground">
                                                                ({attempt.percent}%)
                                                            </span>
                                                        )}
                                                    </span>
                                                </span>
                                            </div>

                                            {attempt.pending && (
                                                <div className="mt-3 grid gap-3">
                                                    {questions
                                                        .filter(
                                                            (question) =>
                                                                attempt.answers[String(question.id)]?.awarded ===
                                                                null,
                                                        )
                                                        .map((question) => {
                                                            const key = `${attempt.id}-${question.id}`;

                                                            return (
                                                                <div
                                                                    key={question.id}
                                                                    className="grid gap-2 rounded-lg border p-3"
                                                                >
                                                                    <p className="text-sm">{question.prompt}</p>
                                                                    <p className="text-xs text-muted-foreground">
                                                                        Answer:{' '}
                                                                        {String(
                                                                            attempt.answers[String(question.id)]
                                                                                ?.given ?? '—',
                                                                        )}
                                                                    </p>
                                                                    <div className="grid gap-2 sm:grid-cols-[8rem_1fr]">
                                                                        <Input
                                                                            type="number"
                                                                            min={0}
                                                                            max={question.points}
                                                                            step={0.5}
                                                                            placeholder={`0-${question.points}`}
                                                                            value={marks[key] ?? ''}
                                                                            onChange={(event) =>
                                                                                setMarks((previous) => ({
                                                                                    ...previous,
                                                                                    [key]: event.target.value,
                                                                                }))
                                                                            }
                                                                            aria-label={`Mark for ${question.prompt}`}
                                                                        />
                                                                        <Textarea
                                                                            rows={2}
                                                                            placeholder="Feedback"
                                                                            value={feedback[key] ?? ''}
                                                                            onChange={(event) =>
                                                                                setFeedback((previous) => ({
                                                                                    ...previous,
                                                                                    [key]: event.target.value,
                                                                                }))
                                                                            }
                                                                            aria-label={`Feedback for ${question.prompt}`}
                                                                        />
                                                                    </div>
                                                                    <Button
                                                                        size="sm"
                                                                        variant="outline"
                                                                        onClick={() => mark(attempt, question)}
                                                                        disabled={saving}
                                                                    >
                                                                        <Save className="size-3.5" aria-hidden />
                                                                        Save mark
                                                                    </Button>
                                                                </div>
                                                            );
                                                        })}
                                                    </div>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                        </CardContent>
                    </Card>
                </div>
            </AppShell>
        </>
    );
}
