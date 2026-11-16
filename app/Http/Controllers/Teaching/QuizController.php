<?php

namespace App\Http\Controllers\Teaching;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\AuditService;
use App\Services\PermissionService;
use App\Services\QuizService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The grader's side of a quiz: the question bank, the attempts, and the marks
 * a human still has to give.
 */
class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function show(Request $request, Course $course, Quiz $quiz): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'quiz.manage', $course), 403);
        abort_unless($this->belongsToCourse($quiz, $course), 404);

        $attempts = QuizAttempt::query()
            ->with('student')
            ->where('quiz_id', $quiz->id)
            ->orderBy('submitted_at')
            ->get();

        return Inertia::render('Teaching/Quiz', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->lesson?->title ?? 'Quiz',
                'max_attempts' => (int) $quiz->max_attempts,
                'pass_mark' => $quiz->pass_mark === null ? null : (float) $quiz->pass_mark,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'question_count' => $quiz->questions()->count(),
                'total_points' => (float) $quiz->questions()->sum('points'),
            ],
            'questions' => $this->quizzes->questionsForGrader($quiz),
            'attempts' => $attempts->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'student' => [
                    'id' => $attempt->student->id,
                    'name' => $attempt->student->name,
                    'email' => $attempt->student->email,
                ],
                'score' => (float) $attempt->score,
                'max_score' => (float) $attempt->max_score,
                'percent' => (float) $attempt->max_score > 0
                    ? round(((float) $attempt->score / (float) $attempt->max_score) * 100, 1)
                    : null,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'pending' => collect($attempt->answers ?? [])->contains(fn (array $answer) => $answer['awarded'] === null),
                'answers' => $attempt->answers ?? [],
            ])->values(),
            'stats' => [
                'attempts' => $attempts->count(),
                'pending' => $attempts->filter(fn (QuizAttempt $attempt) => collect($attempt->answers ?? [])->contains(fn (array $answer) => $answer['awarded'] === null))->count(),
                'average' => $attempts->isEmpty() ? null : round($attempts->avg(fn (QuizAttempt $attempt) => (float) $attempt->score), 2),
            ],
        ]);
    }

    /**
     * Add a question to the bank.
     */
    public function storeQuestion(Request $request, Course $course, Quiz $quiz): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'quiz.manage', $course), 403);
        abort_unless($this->belongsToCourse($quiz, $course), 404);

        $validated = $request->validate([
            'type' => ['required', 'in:multiple_choice,multiple_select,true_false,short_answer,essay'],
            'prompt' => ['required', 'string', 'max:2000'],
            'options' => ['nullable', 'array', 'max:10'],
            'options.*' => ['nullable', 'string', 'max:500'],
            'answer' => ['nullable'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:100'],
            'explanation' => ['nullable', 'string', 'max:2000'],
        ]);

        $question = $quiz->questions()->create([
            'type' => $validated['type'],
            'prompt' => $validated['prompt'],
            'options' => $this->cleanOptions($validated['options'] ?? []),
            'answer' => $this->normalizeAnswer($validated['type'], $validated['answer'] ?? null),
            'points' => (float) $validated['points'],
            'position' => (int) $quiz->questions()->max('position') + 1,
            'explanation' => $validated['explanation'] ?? null,
        ]);

        $this->audit->record(
            'quiz.question.create',
            $question,
            Question::class,
            $question->id,
            $course->id,
            [],
            ['quiz_id' => $quiz->id, 'type' => $question->type->value],
        );

        return back()->with('success', 'Question added.');
    }

    public function destroyQuestion(Request $request, Course $course, Quiz $quiz, Question $question): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'quiz.manage', $course), 403);
        abort_unless($this->belongsToCourse($quiz, $course), 404);
        abort_unless($question->quiz_id === $quiz->id, 404);

        $question->delete();

        $this->audit->record(
            'quiz.question.delete',
            $question,
            Question::class,
            $question->id,
            $course->id,
        );

        return back()->with('success', 'Question removed.');
    }

    /**
     * Give a human mark to a question that auto-grading cannot settle.
     */
    public function mark(Request $request, Course $course, Quiz $quiz, QuizAttempt $attempt): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'submission.grade', $course), 403);
        abort_unless($this->belongsToCourse($quiz, $course), 404);
        abort_unless($attempt->quiz_id === $quiz->id, 404);

        $validated = $request->validate([
            'question_id' => ['required', 'integer', 'exists:questions,id'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $question = Question::query()->findOrFail($validated['question_id']);

        abort_unless($question->quiz_id === $quiz->id, 404);

        $this->quizzes->markManually(
            $attempt,
            $question,
            $validated['points'] === null ? null : (float) $validated['points'],
            $validated['feedback'] ?? null,
        );

        $this->audit->record(
            'quiz.attempt.mark',
            $attempt,
            QuizAttempt::class,
            $attempt->id,
            $course->id,
            [],
            ['question_id' => $question->id, 'points' => $validated['points']],
        );

        return back()->with('success', 'Mark saved.');
    }

    /**
     * @param  list<string|null>  $options
     * @return list<string>
     */
    private function cleanOptions(array $options): array
    {
        return array_values(array_filter(
            array_map(fn (?string $option) => $option === null ? null : trim($option), $options),
            fn (?string $option) => $option !== null && $option !== '',
        ));
    }

    /**
     * @return list<string|bool>|null
     */
    private function normalizeAnswer(string $type, mixed $answer): ?array
    {
        if ($type === 'true_false') {
            return [(bool) $answer];
        }

        if ($type === 'multiple_select') {
            return array_values(array_map('strval', (array) $answer));
        }

        if ($type === 'essay') {
            return [];
        }

        if (is_array($answer)) {
            return array_values(array_map('strval', $answer));
        }

        return $answer === null || $answer === '' ? null : [(string) $answer];
    }

    private function belongsToCourse(Quiz $quiz, Course $course): bool
    {
        return $quiz->lesson?->module?->course_id === $course->id;
    }
}
