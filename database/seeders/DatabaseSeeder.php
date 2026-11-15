<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Course;
use App\Models\Lesson;
use App\Models\Module;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@lightlms.test'],
            [
                'name' => 'LightLMS Admin',
                'password' => 'password',
                'role' => UserRole::Admin,
                'email_verified_at' => now(),
            ],
        );

        User::factory()->count(8)->create();

        $titles = [
            'Introduction to Systems Thinking',
            'Designing Accessible Interfaces',
            'Applied Statistics for Decision Makers',
            'Modern PHP: Patterns and Practices',
            'Learning How to Learn',
            'Data Ethics in Practice',
        ];

        foreach ($titles as $index => $title) {
            $course = Course::factory()->create([
                'title' => $title,
                'slug' => Str::slug($title),
                'published_at' => now()->subDays($index * 3),
            ]);

            for ($m = 1; $m <= 3; $m++) {
                $module = Module::factory()->create([
                    'course_id' => $course->id,
                    'title' => "Module {$m}: Core Concepts",
                    'position' => $m,
                ]);

                for ($l = 1; $l <= 4; $l++) {
                    Lesson::factory()->create([
                        'module_id' => $module->id,
                        'title' => "Lesson {$l}",
                        'position' => $l,
                        'is_preview' => $l === 1 && $m === 1,
                    ]);
                }
            }

            $course->students()->attach($admin->id, ['progress' => 0]);
        }
    }
}
