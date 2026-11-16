<?php

namespace App\Services;

use App\Enums\QuestionType;
use App\Models\GradeItem;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

/**
 * Runs a quiz and marks it.
 *
 * Answers never leave the server: the learner receives the prompts without the
 * correct answers, and grading happens here. Short answers and essays are left
 * for a human, so an attempt can come back partly graded.
 */
class QuizService
{
    public function __construct(
        private readonly GradebookService $gradebook,
    ) {}

    /**
     * The quiz as the learner sees it: prompts and options, never the answers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function questionsForLearner(Quiz $quiz): array
    {
        return $quiz->questions->map(fn (Question $question) => [
            'id' => $question->id,
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'options' => $question->options ?? [],
            'points' => (float) $question->points,
        ])->values()->all();
    }

    /**
     * The quiz as the grader sees it, including the answers.
     *
     * @return array<int, array<string, mixed>>
     */
    public function questionsForGrader(Quiz $quiz): array
    {
        return $quiz->questions->map(fn (Question $question) => [
            'id' => $question->id,
            'type' => $question->type->value,
            'prompt' => $question->prompt,
            'options' => $question->options ?? [],
            'answer' => $question->answer ?? [],
            'points' => (float) $question->points,
            'explanation' => $question->explanation,
        ])->values()->all();
    }

    /**
     * The attempts a learner has already made.
     *
     * @return Collection<int, QuizAttempt>
     */
    public function attempts(Quiz $quiz, User $learner)
    {
        return QuizAttempt::query()
            ->where('quiz_id', $quiz->id)
            ->where('user_id', $learner->id)
            ->orderBy('created_at')
            ->get();
    }

    public function attemptsLeft(Quiz $quiz, User $learner): int
    {
        $max = (int) $quiz->max_attempts;

        return $max === 0 ? PHP_INT_MAX : max(0, $max - $this->attempts($quiz, $learner)->count());
    }

    /**
     * Mark an attempt.
     *
     * Auto-gradable questions are scored here; short answers and essays are
     * left pending for a human.
     *
     * @param  array<int, mixed>  $answers  keyed by question id
     */
    public function grade(Quiz $quiz, User $learner, array $answers): QuizAttempt
    {
        $questions = $quiz->questions;
        $maxScore = (float) $questions->sum('points');
        $score = 0.0;
        $pending = 0;
        $marked = [];

        foreach ($questions as $question) {
            $given = $answers[$question->id] ?? null;
            $correct = $this->isCorrect($question, $given);

            if (! $question->type->isAutoGraded()) {
                $pending++;

                $marked[$question->id] = [
                    'given' => $given,
                    'correct' => null,
                    'awarded' => null,
                ];

                continue;
            }

            $awarded = $correct ? (float) $question->points : 0.0;
            $score += $awarded;

            $marked[$question->id] = [
                'given' => $given,
                'correct' => $correct,
                'awarded' => $awarded,
            ];
        }

        $attempt = QuizAttempt::query()->create([
            'quiz_id' => $quiz->id,
            'user_id' => $learner->id,
            'score' => $score,
            'max_score' => $maxScore,
            'answers' => $marked,
            'started_at' => now(),
            'submitted_at' => now(),
        ]);

        $this->syncGradebook($quiz, $learner, $score, $maxScore, $pending);

        return $attempt;
    }

    /**
     * Store a manual mark for a question a human has to look at.
     */
    public function markManually(QuizAttempt $attempt, Question $question, ?float $points, ?string $feedback = null): QuizAttempt
    {
        $answers = $attempt->answers ?? [];
        $given = $answers[$question->id]['given'] ?? null;
        $awarded = $points === null ? null : max(0.0, min((float) $question->points, $points));

        $answers[$question->id] = [
            'given' => $given,
            'correct' => null,
            'awarded' => $awarded,
            'feedback' => $feedback,
        ];

        $attempt->answers = $answers;

        $graded = collect($answers)->whereNotNull('awarded')->sum('awarded');
        $pending = collect($answers)->contains(fn (array $answer) => $answer['awarded'] === null);

        $attempt->update([
            'answers' => $answers,
            'score' => $graded,
        ]);

        $quiz = $attempt->quiz;
        $course = $quiz->lesson?->module?->course;

        if ($course !== null) {
            $this->syncGradebook($quiz, $attempt->student, $graded, (float) $attempt->max_score, $pending);
        }

        return $attempt;
    }

    /**
     * Whether one answer is right for one question.
     */
    public function isCorrect(Question $question, mixed $given): bool
    {
        $answer = $question->answer ?? [];

        if ($question->type === QuestionType::TrueFalse) {
            return is_bool($given) && $given === (bool) ($answer[0] ?? false);
        }

        if ($question->type === QuestionType::MultipleSelect) {
            $given = is_array($given) ? $given : [];
            sort($given);
            $expected = array_map('strval', $answer);
            sort($expected);

            return $given === $expected;
        }

        if ($question->type === QuestionType::ShortAnswer) {
            $expected = Str::lower(trim((string) ($answer[0] ?? '')));
            $given = Str::lower(trim((string) $given));

            return $expected !== '' && $given === $expected;
        }

        if ($question->type === QuestionType::Essay) {
            return false;
        }

        return (string) $given === (string) ($answer[0] ?? '');
    }

    /**
     * Mirror the attempt into the course gradebook.
     */
    private function syncGradebook(Quiz $quiz, User $learner, float $score, float $maxScore, int $pending): void
    {
        $course = $quiz->lesson?->module?->course;

        if ($course === null) {
            return;
        }

        $this->gradebook->syncItems($course);

        $item = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('source_type', 'activity')
            ->where('source_id', $quiz->lesson_id)
            ->first();

        if ($item === null) {
            return;
        }

        $this->gradebook->saveGrade(
            $item,
            $learner,
            $pending > 0 ? null : round($score, 2),
            null,
            null,
        );
    }
}
