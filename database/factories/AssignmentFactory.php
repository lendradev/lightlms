<?php

namespace Database\Factories;

use App\Models\Assignment;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assignment>
 */
class AssignmentFactory extends Factory
{
    protected $model = Assignment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory()->assignment(),
            'submission_type' => 'text',
            'max_attempts' => 1,
            'allow_late' => true,
            'require_approval' => false,
        ];
    }
}
