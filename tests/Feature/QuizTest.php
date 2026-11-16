<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Setting;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuizTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
    }

    private function quizFor(Course $course, array $questionData = []): Quiz
    {
        $module = Module::factory()->create(['course_id' => $course->id]);
        $lesson = Lesson::factory()->quiz()->create(['module_id' => $module->id, 'grade_max' => 100]);
        $quiz = Quiz::query()->create([
            'lesson_id' => $lesson->id,
            'max_attempts' => 2,
            'pass_mark' => 50,
        ]);

        foreach ($questionData as $index => $data) {
            $quiz->questions()->create(array_merge([
                'type' => 'multiple_choice',
                'prompt' => 'Question '.$index,
                'options' => ['A', 'B'],
                'answer' => ['A'],
                'points' => 1,
                'position' => $index + 1,
            ], $data));
        }

        return $quiz;
    }

    public function test_an_enrolled_student_can_open_a_quiz(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course);
        $course->students()->attach($student->id, ['status' => 'active']);

        $this->actingAs($student)
            ->get("/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Learning/Quiz')->where('quiz.id', $quiz->id));
    }

    public function test_the_answers_are_never_sent_to_the_learner(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [[
            'prompt' => 'Which one is right?',
            'options' => ['A', 'B'],
            'answer' => ['B'],
        ]]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $response = $this->actingAs($student)->get("/quizzes/{$quiz->id}");
        $payload = $response->getContent();

        $this->assertStringNotContainsString('"answer"', $payload);
        $this->assertStringContainsString('Which one is right?', $payload);
    }

    public function test_a_student_outside_the_course_cannot_open_a_quiz(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course);

        $this->actingAs($student)->get("/quizzes/{$quiz->id}")->assertForbidden();
    }

    public function test_an_attempt_is_scored_and_stored(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [
            ['prompt' => 'First', 'options' => ['A', 'B'], 'answer' => ['A'], 'points' => 2],
            ['prompt' => 'Second', 'options' => ['A', 'B'], 'answer' => ['B'], 'points' => 3],
        ]);
        $course->students()->attach($student->id, ['status' => 'active']);

        $questions = $quiz->questions()->orderBy('position')->get();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", [
                'answers' => [
                    $questions[0]->id => 'A',
                    $questions[1]->id => 'A',
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', [
            'quiz_id' => $quiz->id,
            'user_id' => $student->id,
            'score' => 2,
            'max_score' => 5,
        ]);
    }

    public function test_a_true_false_question_is_marked(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [[
            'type' => 'true_false',
            'prompt' => 'Stocks accumulate.',
            'options' => ['True', 'False'],
            'answer' => [true],
            'points' => 1,
        ]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => true]])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id, 'score' => 1]);
    }

    public function test_a_multiple_select_question_needs_every_correct_option(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [[
            'type' => 'multiple_select',
            'prompt' => 'Pick both',
            'options' => ['A', 'B', 'C'],
            'answer' => ['A', 'C'],
            'points' => 4,
        ]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => ['A']]])
            ->assertRedirect();

        $this->assertDatabaseHas('quiz_attempts', ['quiz_id' => $quiz->id, 'score' => 0]);
    }

    public function test_a_short_answer_is_left_for_a_human(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [[
            'type' => 'short_answer',
            'prompt' => 'Name the loop',
            'options' => [],
            'answer' => ['balancing loop'],
            'points' => 2,
        ]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'balancing loop']])
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();

        $this->assertSame(0.0, (float) $attempt->score);
        $this->assertNull($attempt->answers[$question->id]['awarded']);
    }

    public function test_the_attempt_limit_is_enforced(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [['prompt' => 'First', 'options' => ['A'], 'answer' => ['A']]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'A']])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'A']])
            ->assertRedirect();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'A']])
            ->assertSessionHas('error');

        $this->assertSame(2, QuizAttempt::query()->where('quiz_id', $quiz->id)->count());
    }

    public function test_a_marker_can_give_the_manual_marks(): void
    {
        $teacher = User::factory()->instructor()->create();
        $student = User::factory()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = $this->quizFor($course, [[
            'type' => 'short_answer',
            'prompt' => 'Name the loop',
            'options' => [],
            'answer' => ['balancing loop'],
            'points' => 2,
        ]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'balancing loop']])
            ->assertRedirect();

        $attempt = QuizAttempt::query()->where('quiz_id', $quiz->id)->firstOrFail();

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/quizzes/{$quiz->id}/attempts/{$attempt->id}/mark", [
                'question_id' => $question->id,
                'points' => 1.5,
                'feedback' => 'Close, name the type of loop.',
            ])
            ->assertRedirect();

        $attempt->refresh();

        $this->assertSame(1.5, (float) $attempt->score);
        $this->assertSame(1.5, (float) $attempt->answers[$question->id]['awarded']);
    }

    public function test_a_grader_sees_the_question_bank_with_the_answers(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = $this->quizFor($course, [['prompt' => 'Which one?', 'options' => ['A', 'B'], 'answer' => ['B']]]);

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/quizzes/{$quiz->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Teaching/Quiz')->has('questions', 1));
    }

    public function test_a_grader_cannot_open_a_quiz_of_another_course(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $other = Course::factory()->create();
        $quiz = $this->quizFor($other);

        $this->actingAs($teacher)
            ->get("/teaching/{$course->slug}/quizzes/{$quiz->id}")
            ->assertNotFound();
    }

    public function test_a_grader_can_add_a_question(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = $this->quizFor($course);

        $this->actingAs($teacher)
            ->post("/teaching/{$course->slug}/quizzes/{$quiz->id}/questions", [
                'type' => 'multiple_select',
                'prompt' => 'Pick the delays',
                'options' => ['Lead time', 'Interest rate'],
                'answer' => 'Lead time',
                'points' => 2,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('questions', [
            'quiz_id' => $quiz->id,
            'type' => 'multiple_select',
            'prompt' => 'Pick the delays',
        ]);
    }

    public function test_a_grader_can_remove_a_question(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = $this->quizFor($course, [['prompt' => 'Which one?', 'options' => ['A', 'B'], 'answer' => ['B']]]);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($teacher)
            ->delete("/teaching/{$course->slug}/quizzes/{$quiz->id}/questions/{$question->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('questions', ['id' => $question->id]);
    }

    public function test_a_student_cannot_add_a_question(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course);

        $this->actingAs($student)
            ->post("/teaching/{$course->slug}/quizzes/{$quiz->id}/questions", [
                'type' => 'multiple_choice',
                'prompt' => 'Sneaky',
                'points' => 1,
            ])
            ->assertForbidden();
    }

    public function test_a_scored_attempt_lands_in_the_gradebook(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [['prompt' => 'First', 'options' => ['A'], 'answer' => ['A'], 'points' => 5]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'A']])
            ->assertRedirect();

        $this->assertDatabaseHas('grade_grades', [
            'user_id' => $student->id,
            'final_points' => 5,
            'status' => 'graded',
            'source' => 'auto',
        ]);
    }

    public function test_adding_a_quiz_activity_creates_the_quiz_record(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $module = Module::factory()->create(['course_id' => $course->id]);

        $this->actingAs($teacher)
            ->post("/teaching/courses/{$course->slug}/lessons", [
                'module_id' => $module->id,
                'title' => 'Check your understanding',
                'type' => 'quiz',
            ])
            ->assertRedirect();

        $lesson = Lesson::query()->where('title', 'Check your understanding')->firstOrFail();

        $this->assertDatabaseHas('quizzes', ['lesson_id' => $lesson->id]);
    }

    public function test_an_attempt_is_audited(): void
    {
        $student = User::factory()->create();
        $course = Course::factory()->create();
        $quiz = $this->quizFor($course, [['prompt' => 'First', 'options' => ['A'], 'answer' => ['A']]]);
        $course->students()->attach($student->id, ['status' => 'active']);
        $question = $quiz->questions()->firstOrFail();

        $this->actingAs($student)
            ->post("/quizzes/{$quiz->id}", ['answers' => [$question->id => 'A']])
            ->assertRedirect();

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $student->id,
            'event' => 'quiz.attempt',
            'course_id' => $course->id,
        ]);
    }

    public function test_a_question_cannot_be_removed_from_another_quiz(): void
    {
        $teacher = User::factory()->instructor()->create();
        $course = Course::factory()->create(['user_id' => $teacher->id]);
        $quiz = $this->quizFor($course);
        $otherQuiz = $this->quizFor($course, [['prompt' => 'Other', 'options' => ['A'], 'answer' => ['A']]]);
        $question = $otherQuiz->questions()->firstOrFail();

        $this->actingAs($teacher)
            ->delete("/teaching/{$course->slug}/quizzes/{$quiz->id}/questions/{$question->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('questions', ['id' => $question->id]);
    }
}
