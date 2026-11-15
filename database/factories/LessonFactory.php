<?php

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Lesson;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    protected $model = Lesson::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'module_id' => Module::factory(),
            'title' => fake()->sentence(4),
            'type' => fake()->randomElement(ActivityType::cases()),
            'position' => fake()->numberBetween(1, 30),
            'duration_minutes' => fake()->numberBetween(3, 90),
            'body' => fake()->paragraphs(2, true),
            'is_preview' => false,
            'grade_max' => 100,
        ];
    }

    public function reading(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ActivityType::Reading]);
    }

    public function quiz(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ActivityType::Quiz]);
    }

    public function assignment(): static
    {
        return $this->state(fn (array $attributes) => ['type' => ActivityType::Assignment]);
    }
}
