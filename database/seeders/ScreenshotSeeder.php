<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\QuestionType;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\FileAsset;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\Lesson;
use App\Models\Notification;
use App\Models\Quiz;
use App\Models\Submission;
use App\Models\User;
use App\Services\CertificateService;
use App\Services\GradebookService;
use App\Services\NotificationService;
use App\Services\QuizService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Fills the demo environment with the records the screenshot catalogue needs.
 *
 * The screenshots show a system in use: marks in the gradebook, a hand-in that
 * has been graded, a quiz that has been attempted, notifications waiting in the
 * inbox, and course material that can be downloaded. A database with only the
 * course skeleton cannot show any of that.
 */
class ScreenshotSeeder extends Seeder
{
    public function run(): void
    {
        $instructor = User::query()->where('email', 'instructor@lightlms.test')->firstOrFail();
        $student = User::query()->where('email', 'student@lightlms.test')->firstOrFail();
        $admin = User::query()->where('email', 'admin@lightlms.test')->firstOrFail();

        $course = Course::query()->where('slug', 'systems-thinking-for-public-policy')->firstOrFail();
        $second = Course::query()->where('slug', 'designing-for-accessibility')->firstOrFail();

        // More people in the roster, so the roster and the gradebook are not empty.
        foreach (['Nadia Nwosu', 'Petra Vogel', 'Rui Almeida', 'Sana Iqbal'] as $index => $name) {
            User::query()->firstOrCreate(
                ['email' => strtolower(str_replace(' ', '.', $name)).'@lightlms.test'],
                [
                    'name' => $name,
                    'password' => Hash::make('password12345'),
                    'role' => 'student',
                    'email_verified_at' => now(),
                ],
            );
        }

        $roster = User::query()->where('email', 'like', '%@lightlms.test')->get();

        foreach ($roster as $index => $person) {
            DB::table('course_user')->updateOrInsert(
                ['course_id' => $course->id, 'user_id' => $person->id],
                [
                    'role' => 'student',
                    'status' => 'active',
                    'progress' => [100, 66, 33, 100, 0][$index % 5],
                    'enrolled_at' => now()->subDays(20 - $index),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        DB::table('course_user')->updateOrInsert(
            ['course_id' => $course->id, 'user_id' => $instructor->id],
            [
                'role' => 'instructor',
                'status' => 'active',
                'progress' => 0,
                'enrolled_at' => now()->subDays(30),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );

        // Course material on a reading, so the materials panel is not empty.
        $materialLesson = Lesson::query()
            ->whereHas('module', fn ($query) => $query->where('course_id', $course->id))
            ->where('type', ActivityType::Reading)
            ->first();

        if ($materialLesson !== null) {
            $stored = 'demo/'.now()->format('Y/m').'/'.(string) Str::uuid().'.pdf';
            Storage::disk('local')->put($stored, "%PDF-1.4\n% demo course material\n");

            FileAsset::query()->firstOrCreate(
                ['lesson_id' => $materialLesson->id, 'original_name' => 'system-mapping-template.pdf'],
                [
                    'uploader_id' => $instructor->id,
                    'disk' => 'local',
                    'path' => $stored,
                    'mime_type' => 'application/pdf',
                    'size' => 42,
                ],
            );
        }

        // A graded hand-in with feedback.
        $assignment = Assignment::query()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
            ->first();

        if ($assignment !== null) {
            Submission::query()->updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $student->id],
                [
                    'content' => 'I mapped the cycle of a city bus route. The more buses that run, the more reliable the service feels, which brings more riders, which needs more buses. The balancing loop is congestion: more buses slow each other down, so headways stretch and riders wait longer.',
                    'status' => 'graded',
                    'submitted_at' => now()->subDays(2),
                    'graded_at' => now()->subDay(),
                    'graded_by' => $instructor->id,
                    'score' => 78,
                    'feedback' => 'Clear stocks and flows, and the two loops are named correctly. Tighten the delay: say how long it is and what it does to the rider.',
                ],
            );

            Submission::query()->updateOrCreate(
                ['assignment_id' => $assignment->id, 'user_id' => $roster->firstWhere('email', 'nadia.nwosu@lightlms.test')->id],
                [
                    'content' => 'I mapped the funding cycle of a grant programme. Money promised attracts applicants, which raises the pool, which lets the programme promise more money next year.',
                    'status' => 'submitted',
                    'submitted_at' => now()->subHours(6),
                ],
            );
        }

        // A graded quiz with a marked attempt.
        $quiz = Quiz::query()
            ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $course->id))
            ->first();

        if ($quiz !== null) {
            $answers = [];

            foreach ($quiz->questions as $question) {
                $answer = $question->answer ?? [];

                $answers[$question->id] = match ($question->type) {
                    QuestionType::TrueFalse => (bool) ($answer[0] ?? false),
                    QuestionType::MultipleSelect => array_values(array_map('strval', $answer)),
                    QuestionType::ShortAnswer => (string) ($answer[0] ?? ''),
                    QuestionType::Essay => 'A short reflection on the feedback structure of the system.',
                    default => (string) ($answer[0] ?? ''),
                };
            }

            app(QuizService::class)->grade($quiz, $student, $answers);
        }

        // Marks in the gradebook, with categories and weights.
        $gradebook = app(GradebookService::class);
        $gradebook->syncItems($course);

        $exams = $this->category($gradebook, $course, 'Exams', 3);
        $homework = $this->category($gradebook, $course, 'Homework', 1);
        $project = $this->category($gradebook, $course, 'Project', 2);

        $items = GradeItem::query()->where('course_id', $course->id)->orderBy('sort_order')->get();

        foreach ($items as $index => $item) {
            $item->update([
                'category_id' => match ($index % 3) {
                    0 => $exams->id,
                    1 => $homework->id,
                    default => $project->id,
                },
                'weight' => [3, 1, 2][$index % 3],
            ]);
        }

        $marks = [
            92, 78, 85, 64, 71, 88, 95, 59, 82, 90, 45, 68,
        ];

        foreach ($roster as $index => $person) {
            foreach ($items as $itemIndex => $item) {
                $mark = $marks[($index + $itemIndex) % count($marks)];

                $gradebook->saveGrade($item, $person, (float) $mark, null, $instructor);
            }
        }

        // The learner transcript needs feedback on some of the marks.
        $studentItems = GradeItem::query()
            ->where('course_id', $course->id)
            ->get();

        foreach ($studentItems as $item) {
            $mark = [88, 71, 95, 64][$item->id % 4];

            $gradebook->saveGrade(
                $item,
                $student,
                (float) $mark,
                $item->id % 2 === 0 ? 'Good work. Name the delay as well as the loop.' : null,
                $instructor,
            );
        }

        // Notifications waiting in the inbox.
        $notifications = app(NotificationService::class);
        $notifications->syncEvents();

        foreach ($roster as $person) {
            if ($person->id === $student->id) {
                continue;
            }

            if (Notification::query()
                ->where('user_id', $person->id)
                ->where('type', 'submission.received')
                ->exists()) {
                continue;
            }

            $notifications->notify(
                $person,
                'submission.received',
                'Nadia handed in "Map a system you care about" in '.$course->title.'.',
                '/learning/'.$course->slug,
                ['course' => $course->title],
            );
        }

        if (! Notification::query()
            ->where('user_id', $instructor->id)
            ->where('type', 'submission.received')
            ->exists()) {
            $notifications->notify(
                $instructor,
                'submission.received',
                'Nadia handed in "Map a system you care about" in '.$course->title.'.',
                '/teaching/'.$course->slug.'/assignments/'.($assignment?->id ?? 1),
                ['course' => $course->title],
            );
        }

        if (! Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'course.published')
            ->exists()) {
            $notifications->notify(
                $admin,
                'course.published',
                'Rui Almeida published "Quantitative Reasoning for Analysts".',
                '/admin/users',
                ['name' => 'Rui Almeida'],
            );
        }

        // A certificate, so the learner and the teacher lists are not empty.
        $certificates = app(CertificateService::class);
        $certificates->issue($course, $student, $instructor);
        $certificates->issue($second, $roster->firstWhere('email', 'nadia.nwosu@lightlms.test'), $instructor);

        $this->command?->info('Screenshot data ready.');
    }

    /**
     * The category with this name, or a new one. Re-running the seeder must not
     * leave two categories with the same name in the same course.
     */
    private function category(
        GradebookService $gradebook,
        Course $course,
        string $name,
        float $weight,
    ): GradeCategory {
        $matches = $gradebook->categories($course)
            ->filter(fn ($category) => $category->parent_id === null && $category->name === $name)
            ->values();

        $existing = $matches->first();

        if ($existing !== null) {
            $gradebook->updateCategory($existing, $name, null, 'weighted_mean', $weight, false);

            foreach ($matches->skip(1) as $duplicate) {
                $gradebook->deleteCategory($duplicate);
            }

            return $existing;
        }

        return $gradebook->saveCategory($course, $name, null, 'weighted_mean', $weight);
    }
}
