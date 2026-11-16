<?php

namespace Database\Seeders;

use App\Enums\ActivityType;
use App\Enums\CourseStatus;
use App\Enums\QuestionType;
use App\Enums\UserRole;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Setting;
use App\Models\Submission;
use App\Models\User;
use App\Support\Setup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        Setting::put(Setup::KEY_INSTALLED, 'true', 'setup');
        Setting::put(Setup::KEY_SITE_NAME, 'LightLMS', 'general');
        Setting::put(Setup::KEY_REGISTRATION, Setup::REGISTRATION_OPEN, 'auth');

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@lightlms.test'],
            ['name' => 'Ada Admin', 'password' => Hash::make('password12345'), 'role' => UserRole::Admin, 'email_verified_at' => now()],
        );

        $instructor = User::query()->firstOrCreate(
            ['email' => 'instructor@lightlms.test'],
            ['name' => 'Iris Instructor', 'password' => Hash::make('password12345'), 'role' => UserRole::Instructor, 'email_verified_at' => now()],
        );

        $student = User::query()->firstOrCreate(
            ['email' => 'student@lightlms.test'],
            ['name' => 'Sam Student', 'password' => Hash::make('password12345'), 'role' => UserRole::Student, 'email_verified_at' => now()],
        );

        $courses = [
            [
                'title' => 'Systems Thinking for Public Policy',
                'summary' => 'Learn to see policy problems as systems: feedback loops, delays, and unintended consequences.',
                'description' => 'This course walks through the core ideas of systems thinking and applies them to live public policy questions. You will model a system, find its feedback structure, and practise reading a policy through that lens.',
                'level' => 'intermediate',
                'duration_minutes' => 420,
                'modules' => [
                    ['Foundations', 'What a system is and why our intuitions fail.', [
                        ['Reading', 'Why systems thinking matters', 25],
                        ['Video', 'Feedback loops in plain language', 18],
                    ]],
                    ['Modelling', 'Turning a messy situation into a diagram.', [
                        ['Reading', 'Stocks, flows and delays', 30],
                        ['Assignment', 'Map a system you care about', 60],
                    ]],
                    ['Application', 'Reading policy through the lens.', [
                        ['Reading', 'Unintended consequences', 22],
                        ['Forum', 'Discuss: a policy you have seen fail', 20],
                        ['Quiz', 'Check your understanding of systems', 15],
                    ]],
                ],
            ],
            [
                'title' => 'Designing for Accessibility',
                'summary' => 'Practical accessibility: contrast, focus order, semantics, and testing with real users.',
                'description' => 'A hands-on course on building interfaces that work for everyone. Every module ends with a concrete change you can apply to a product you already work on.',
                'level' => 'beginner',
                'duration_minutes' => 300,
                'modules' => [
                    ['Perceivable', 'What people can perceive.', [
                        ['Reading', 'Contrast that actually works', 20],
                        ['Reading', 'Colour is not information', 15],
                    ]],
                    ['Operable', 'What people can operate.', [
                        ['Reading', 'Keyboard paths and focus order', 25],
                        ['Quiz', 'Check your understanding', 10],
                    ]],
                ],
            ],
            [
                'title' => 'Quantitative Reasoning for Analysts',
                'summary' => 'Read a dataset, build a model, and communicate the result without overstating it.',
                'description' => 'For analysts who want to be rigorous. We cover sampling, distributions, uncertainty, and how to write down an assumption before you check whether it held.',
                'level' => 'advanced',
                'duration_minutes' => 540,
                'modules' => [
                    ['Foundations', 'What the data can and cannot tell you.', [
                        ['Reading', 'Sampling and selection', 28],
                        ['Video', 'Distributions in the wild', 21],
                    ]],
                    ['Modelling', 'From question to model.', [
                        ['Reading', 'Choosing a model shape', 35],
                        ['Assignment', 'Model a dataset you use at work', 90],
                    ]],
                ],
            ],
        ];

        foreach ($courses as $data) {
            $course = Course::query()->firstOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'description' => $data['description'],
                    'level' => $data['level'],
                    'duration_minutes' => $data['duration_minutes'],
                    'instructor_name' => $instructor->name,
                    'user_id' => $instructor->id,
                    'status' => CourseStatus::Published,
                    'published_at' => now()->subDays(random_int(1, 30)),
                    'language' => 'en',
                    'currency' => 'USD',
                    'price' => 0,
                    'self_enroll' => true,
                ],
            );

            foreach ($data['modules'] as $moduleIndex => [$moduleTitle, $moduleSummary, $lessons]) {
                $module = Module::query()->firstOrCreate(
                    ['course_id' => $course->id, 'position' => $moduleIndex + 1],
                    ['title' => $moduleTitle, 'summary' => $moduleSummary, 'visible' => true],
                );

                foreach ($lessons as $lessonIndex => [$type, $title, $minutes]) {
                    $lesson = Lesson::query()->firstOrCreate(
                        ['module_id' => $module->id, 'position' => $lessonIndex + 1],
                        [
                            'title' => $title,
                            'type' => strtolower($type),
                            'duration_minutes' => $minutes,
                            'body' => 'This activity walks through '.$title.'. Read it carefully, then continue to the next one.',
                            'is_preview' => $moduleIndex === 0 && $lessonIndex === 0,
                            'grade_max' => 100,
                        ],
                    );

                    if ($lesson->type === ActivityType::Assignment) {
                        Assignment::query()->firstOrCreate(
                            ['lesson_id' => $lesson->id],
                            [
                                'submission_type' => 'text',
                                'max_attempts' => 3,
                                'allow_late' => true,
                                'require_approval' => false,
                            ],
                        );

                        $lesson->update([
                            'instructions' => 'Describe the system you mapped: name its stocks and flows, point out at least one reinforcing loop and one balancing loop, and explain what happens if you delay one of the flows.',
                            'due_at' => now()->addDays(14),
                        ]);
                    }
                    if ($lesson->type === ActivityType::Quiz) {
                        $quiz = Quiz::query()->firstOrCreate(
                            ['lesson_id' => $lesson->id],
                            [
                                'max_attempts' => 3,
                                'shuffle_questions' => false,
                                'pass_mark' => 60,
                                'time_limit_minutes' => 20,
                            ],
                        );

                        $lesson->update([
                            'instructions' => 'Answer every question. Short answers and essays are read by a marker; the rest are marked as soon as you submit.',
                        ]);

                        $questions = [
                            [
                                'type' => QuestionType::MultipleChoice,
                                'prompt' => 'Which pair describes a reinforcing loop?',
                                'options' => ['More cars, more drivers', 'More drivers, more cars', 'Fewer cars, fewer drivers', 'Neither pair is a loop'],
                                'answer' => ['More drivers, more cars'],
                                'points' => 2,
                                'explanation' => 'A reinforcing loop amplifies what it is attached to: more drivers means more cars, which means more drivers.',
                            ],
                            [
                                'type' => QuestionType::MultipleSelect,
                                'prompt' => 'Which of these are delays?',
                                'options' => ['Order processing time', 'Interest rate', 'Shipping lead time', 'Panel recruitment lag'],
                                'answer' => ['Order processing time', 'Shipping lead time', 'Panel recruitment lag'],
                                'points' => 3,
                                'explanation' => 'A delay is time between an action and its effect. Interest rate acts immediately, so it is not a delay.',
                            ],
                            [
                                'type' => QuestionType::TrueFalse,
                                'prompt' => 'A stock is a quantity that accumulates over time.',
                                'options' => ['True', 'False'],
                                'answer' => [true],
                                'points' => 1,
                                'explanation' => 'Stocks accumulate; flows change them over time.',
                            ],
                            [
                                'type' => QuestionType::ShortAnswer,
                                'prompt' => 'Name the loop that keeps a policy problem in place.',
                                'options' => [],
                                'answer' => ['balancing loop'],
                                'points' => 2,
                                'explanation' => 'A balancing loop counteracts change and holds the system steady.',
                            ],
                            [
                                'type' => QuestionType::Essay,
                                'prompt' => 'Explain why a policy intervention can fail even when it targets the right stock.',
                                'options' => [],
                                'answer' => [],
                                'points' => 4,
                                'explanation' => 'Look for the surrounding structure: the intervention may push on a stock while the reinforcing loop rebuilds it.',
                            ],
                        ];

                        foreach ($questions as $index => $question) {
                            Question::query()->firstOrCreate(
                                ['quiz_id' => $quiz->id, 'position' => $index + 1],
                                [
                                    'type' => $question['type'],
                                    'prompt' => $question['prompt'],
                                    'options' => $question['options'],
                                    'answer' => $question['answer'],
                                    'points' => $question['points'],
                                    'explanation' => $question['explanation'],
                                ],
                            );
                        }
                    }
                }
            }
        }

        $firstCourse = Course::query()->orderBy('id')->first();

        if ($firstCourse !== null) {
            DB::table('course_user')->updateOrInsert(
                ['course_id' => $firstCourse->id, 'user_id' => $student->id],
                ['status' => 'active', 'progress' => 33, 'enrolled_at' => now()->subDays(5), 'created_at' => now(), 'updated_at' => now()],
            );

            $lesson = Lesson::query()->where('module_id', Module::query()->where('course_id', $firstCourse->id)->value('id'))->first();

            if ($lesson !== null) {
                DB::table('lesson_user')->updateOrInsert(
                    ['lesson_id' => $lesson->id, 'user_id' => $student->id],
                    ['completed' => true, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                );
            }

            $assignment = Assignment::query()
                ->whereHas('lesson.module', fn ($query) => $query->where('course_id', $firstCourse->id))
                ->first();

            if ($assignment !== null) {
                Submission::query()->firstOrCreate(
                    ['assignment_id' => $assignment->id, 'user_id' => $student->id],
                    [
                        'content' => 'I mapped the cycle of a city bus route: the more buses that run, the more reliable the service feels, which brings more riders, which needs more buses. The balancing loop is congestion: more buses slow each other down, so headways stretch and riders wait longer.',
                        'status' => 'submitted',
                        'submitted_at' => now()->subDays(2),
                    ],
                );
            }
        }

        $this->command?->info('Demo data ready. Sign in with admin@lightlms.test / password12345');
    }
}
