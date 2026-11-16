import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, CircleHelp, Clock, Send } from 'lucide-react';
import { useState } from 'react';
import { AppShell } from '@/components/AppShell';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Textarea } from '@/components/ui/textarea';

type Question = {
    id: number;
    type: 'multiple_choice' | 'multiple_select' | 'true_false' | 'short_answer' | 'essay';
    prompt: string;
    options: string[];
    points: number;
};

type Attempt = {
    id: number;
    score: number;
    max_score: number;
    percent: number | null;
    submitted_at: string | null;
    pending: boolean;
};

type Props = {
    course: { id: number; title: string; slug: string };
    quiz: {
        id: number;
        title: string;
        instructions: string | null;
        time_limit_minutes: number | null;
        max_attempts: number;
        pass_mark: number | null;
        question_count: number;
        total_points: number;
    };
    questions: Question[];
    attemptsLeft: number;
    attempts: Attempt[];
    latest: {
        id: number;
        score: number;
        max_score: number;
        percent: number | null;
        answers: Record<string, { given: unknown; correct: boolean | null; awarded: number | null }>;
        submitted_at: string | null;
    } | null;
};

const TYPE_LABELS: Record<Question['type'], string> = {
    multiple_choice: 'Multiple choice',
    multiple_select: 'Multiple select',
    true_false: 'True or false',
    short_answer: 'Short answer',
    essay: 'Essay',
};

type AnswerValue = string | string[] | boolean | null;

export default function LearningQuiz({ course, quiz, questions, attemptsLeft, attempts, latest }: Props) {
    const [answers, setAnswers] = useState<Record<number, AnswerValue>>({});
    const form = useForm<{ answers: Record<string, AnswerValue> }>({ answers: {} });

    const exhausted = attemptsLeft <= 0;

    const setAnswer = (questionId: number, value: AnswerValue) => {
        const next = { ...answers, [questionId]: value };

        setAnswers(next);
        form.setData(
            'answers',
            Object.fromEntries(Object.entries(next).map(([key, item]) => [key, item])),
        );
    };

    const submit = () => {
        form.post(`/quizzes/${quiz.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setAnswers({});
                form.reset('answers');
            },
        });
    };

    return (
        <>
            <Head title={quiz.title} />

            <AppShell title={quiz.title}>
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
                            <Badge variant="outline">Quiz</Badge>
                            {latest?.percent !== null && latest?.percent !== undefined && (
                                <Badge variant="secondary">Best {Math.max(...attempts.map((a) => a.percent ?? 0))}%</Badge>
                            )}
                        </div>
                        <h2 className="mt-2 text-balance font-serif text-2xl font-semibold tracking-tight">
                            {quiz.title}
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            {quiz.question_count} questions · {quiz.total_points} points
                            {quiz.time_limit_minutes && (
                                <>
                                    {' · '}
                                    <span className="inline-flex items-center gap-1">
                                        <Clock className="size-3" aria-hidden />
                                        {quiz.time_limit_minutes} min
                                    </span>
                                </>
                            )}
                        </p>
                    </div>

                    {quiz.instructions && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Instructions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                                    {quiz.instructions}
                                </p>
                            </CardContent>
                        </Card>
                    )}

                    {latest && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Last attempt</CardTitle>
                                <CardDescription>
                                    {latest.score} of {latest.max_score} points
                                    {latest.percent !== null && ` (${latest.percent}%)`}
                                    {quiz.pass_mark !== null && ` · pass mark ${quiz.pass_mark}%`}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <ul className="space-y-2">
                                    {questions.map((question) => {
                                        const answer = latest.answers[String(question.id)];
                                        const pending = answer?.awarded === null;

                                        return (
                                            <li key={question.id} className="flex items-start gap-2 text-sm">
                                                {pending ? (
                                                    <CircleHelp
                                                        className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                                                        aria-label="Waiting for a marker"
                                                    />
                                                ) : answer?.correct ? (
                                                    <CheckCircle2
                                                        className="mt-0.5 size-4 shrink-0 text-secondary"
                                                        aria-label="Correct"
                                                    />
                                                ) : (
                                                    <CircleHelp
                                                        className="mt-0.5 size-4 shrink-0 text-destructive"
                                                        aria-label="Incorrect"
                                                    />
                                                )}
                                                <span className="min-w-0">
                                                    <span className="block">{question.prompt}</span>
                                                    <span className="block text-xs text-muted-foreground">
                                                        {pending
                                                            ? 'Waiting for a marker'
                                                            : `${answer?.awarded ?? 0} of ${question.points} points`}
                                                    </span>
                                                </span>
                                            </li>
                                        );
                                    })}
                                </ul>
                            </CardContent>
                        </Card>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-sm">
                                {exhausted ? 'No attempts left' : 'Answer the questions'}
                            </CardTitle>
                            <CardDescription>
                                {exhausted
                                    ? 'You have used every attempt for this quiz.'
                                    : `${attemptsLeft} attempt${attemptsLeft === 1 ? '' : 's'} left.`}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            {questions.map((question, index) => (
                                <fieldset key={question.id} className="grid gap-2">
                                    <legend className="text-sm font-medium">
                                        {index + 1}. {question.prompt}
                                        <span className="ml-2 text-xs font-normal text-muted-foreground">
                                            {question.points} {question.points === 1 ? 'point' : 'points'}
                                        </span>
                                    </legend>
                                    <span className="text-xs uppercase tracking-wide text-muted-foreground">
                                        {TYPE_LABELS[question.type]}
                                    </span>

                                    {question.type === 'multiple_choice' && (
                                        <RadioGroup
                                            value={(answers[question.id] as string) ?? ''}
                                            onValueChange={(value) => setAnswer(question.id, value)}
                                            disabled={exhausted}
                                        >
                                            {question.options.map((option) => (
                                                <div key={option} className="flex items-center gap-2">
                                                    <RadioGroupItem
                                                        value={option}
                                                        id={`question-${question.id}-${option}`}
                                                    />
                                                    <Label
                                                        htmlFor={`question-${question.id}-${option}`}
                                                        className="font-normal"
                                                    >
                                                        {option}
                                                    </Label>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                    )}

                                    {question.type === 'multiple_select' && (
                                        <div className="grid gap-2">
                                            {question.options.map((option) => {
                                                const selected = ((answers[question.id] as string[]) ?? []).includes(
                                                    option,
                                                );

                                                return (
                                                    <div key={option} className="flex items-center gap-2">
                                                        <Checkbox
                                                            id={`question-${question.id}-${option}`}
                                                            checked={selected}
                                                            disabled={exhausted}
                                                            onCheckedChange={(checked) => {
                                                                const current =
                                                                    ((answers[question.id] as string[]) ??
                                                                    []).filter(
                                                                        (item) => item !== option,
                                                                    );

                                                                setAnswer(
                                                                    question.id,
                                                                    checked === true
                                                                        ? [...current, option]
                                                                        : current,
                                                                );
                                                            }}
                                                        />
                                                        <Label
                                                            htmlFor={`question-${question.id}-${option}`}
                                                            className="font-normal"
                                                        >
                                                            {option}
                                                        </Label>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    )}

                                    {question.type === 'true_false' && (
                                        <RadioGroup
                                            value={(answers[question.id] as string) ?? ''}
                                            onValueChange={(value) => setAnswer(question.id, value === 'true')}
                                            disabled={exhausted}
                                        >
                                            {[
                                                { label: 'True', value: 'true' },
                                                { label: 'False', value: 'false' },
                                            ].map((option) => (
                                                <div key={option.value} className="flex items-center gap-2">
                                                    <RadioGroupItem
                                                        value={option.value}
                                                        id={`question-${question.id}-${option.value}`}
                                                    />
                                                    <Label
                                                        htmlFor={`question-${question.id}-${option.value}`}
                                                        className="font-normal"
                                                    >
                                                        {option.label}
                                                    </Label>
                                                </div>
                                            ))}
                                        </RadioGroup>
                                    )}

                                    {question.type === 'short_answer' && (
                                        <Textarea
                                            rows={2}
                                            value={(answers[question.id] as string) ?? ''}
                                            onChange={(event) => setAnswer(question.id, event.target.value)}
                                            placeholder="Your answer"
                                            disabled={exhausted}
                                        />
                                    )}

                                    {question.type === 'essay' && (
                                        <Textarea
                                            rows={6}
                                            value={(answers[question.id] as string) ?? ''}
                                            onChange={(event) => setAnswer(question.id, event.target.value)}
                                            placeholder="Write your answer"
                                            disabled={exhausted}
                                        />
                                    )}
                                </fieldset>
                            ))}

                            {form.errors.answers && (
                                <p className="text-xs text-destructive">{form.errors.answers}</p>
                            )}

                            <Button onClick={submit} disabled={form.processing || exhausted}>
                                <Send className="size-4" aria-hidden />
                                Submit answers
                            </Button>
                        </CardContent>
                    </Card>

                    {attempts.length > 0 && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="text-sm">Your attempts</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ul className="divide-y">
                                    {attempts.map((attempt, index) => (
                                        <li key={attempt.id} className="flex items-center justify-between gap-3 py-2.5 text-sm">
                                            <span>
                                                Attempt {index + 1}
                                                {attempt.pending && (
                                                    <span className="ml-2 text-xs text-muted-foreground">
                                                        awaiting a marker
                                                    </span>
                                                )}
                                            </span>
                                            <span className="font-medium tabular-nums">
                                                {attempt.score}/{attempt.max_score}
                                                {attempt.percent !== null && (
                                                    <span className="ml-1 text-xs text-muted-foreground">
                                                        ({attempt.percent}%)
                                                    </span>
                                                )}
                                            </span>
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
