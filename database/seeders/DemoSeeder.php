<?php

namespace Database\Seeders;

use App\Enums\CourseStatus;
use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\Setting;
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
                    Lesson::query()->firstOrCreate(
                        ['module_id' => $module->id, 'position' => $lessonIndex + 1],
                        [
                            'title' => $title,
                            'type' => strtolower($type),
                            'duration_minutes' => $minutes,
                            'body' => 'This activity walks through '.$title.'. Read it carefully, then continue to the next one.',
                            'is_preview' => $moduleIndex === 0 && $lessonIndex === 0,
                        ],
                    );
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
        }

        $this->command?->info('Demo data ready. Sign in with admin@lightlms.test / password12345');
    }
}
