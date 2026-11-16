<?php

namespace App\Http\Controllers\Learning;

use App\Http\Controllers\Controller;
use App\Models\Course;
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
 * The learner's side of a quiz: answer the questions, see the result.
 */
class QuizController extends Controller
{
    public function __construct(
        private readonly QuizService $quizzes,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function show(Request $request, Quiz $quiz): Response
    {
        $user = $request->user();
        $course = $this->courseFor($quiz);

        abort_unless(
            $this->permissions->allows($user, 'quiz.attempt', $course),
            403,
            'You do not have access to this quiz.',
        );

        $attempts = $this->quizzes->attempts($quiz, $user);
        $latest = $attempts->last();

        return Inertia::render('Learning/Quiz', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'quiz' => [
                'id' => $quiz->id,
                'title' => $quiz->lesson?->title ?? 'Quiz',
                'instructions' => $quiz->lesson?->instructions,
                'time_limit_minutes' => $quiz->time_limit_minutes,
                'max_attempts' => (int) $quiz->max_attempts,
                'pass_mark' => $quiz->pass_mark === null ? null : (float) $quiz->pass_mark,
                'question_count' => $quiz->questions()->count(),
                'total_points' => (float) $quiz->questions()->sum('points'),
            ],
            'questions' => $this->quizzes->questionsForLearner($quiz),
            'attemptsLeft' => $this->quizzes->attemptsLeft($quiz, $user),
            'attempts' => $attempts->map(fn (QuizAttempt $attempt) => [
                'id' => $attempt->id,
                'score' => (float) $attempt->score,
                'max_score' => (float) $attempt->max_score,
                'percent' => (float) $attempt->max_score > 0
                    ? round(((float) $attempt->score / (float) $attempt->max_score) * 100, 1)
                    : null,
                'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                'pending' => collect($attempt->answers ?? [])->contains(fn (array $answer) => $answer['awarded'] === null),
            ])->values(),
            'latest' => $latest === null ? null : [
                'id' => $latest->id,
                'score' => (float) $latest->score,
                'max_score' => (float) $latest->max_score,
                'percent' => (float) $latest->max_score > 0
                    ? round(((float) $latest->score / (float) $latest->max_score) * 100, 1)
                    : null,
                'answers' => $latest->answers ?? [],
                'submitted_at' => $latest->submitted_at?->toIso8601String(),
            ],
        ]);
    }

    public function store(Request $request, Quiz $quiz): RedirectResponse
    {
        $user = $request->user();
        $course = $this->courseFor($quiz);

        abort_unless(
            $this->permissions->allows($user, 'quiz.attempt', $course),
            403,
            'You do not have access to this quiz.',
        );

        if ($this->quizzes->attemptsLeft($quiz, $user) === 0) {
            return back()->with('error', 'You have used every attempt for this quiz.');
        }

        $validated = $request->validate([
            'answers' => ['required', 'array'],
            'answers.*' => ['nullable'],
        ]);

        $attempt = $this->quizzes->grade($quiz, $user, $validated['answers']);

        $this->audit->record(
            'quiz.attempt',
            $attempt,
            QuizAttempt::class,
            $attempt->id,
            $course->id,
            [],
            ['quiz_id' => $quiz->id, 'score' => (float) $attempt->score],
        );

        return back()->with('success', 'Quiz submitted.');
    }

    private function courseFor(Quiz $quiz): Course
    {
        $course = $quiz->lesson?->module?->course;

        abort_if($course === null, 404, 'This quiz is not attached to a course.');

        return $course;
    }
}
