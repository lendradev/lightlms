<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    protected $model = Module::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'title' => fake()->sentence(3),
            'summary' => fake()->sentence(10),
            'position' => static fn (array $attributes): int => (int) Module::query()
                ->where('course_id', $attributes['course_id'])
                ->max('position') + 1,
            'visible' => true,
        ];
    }
}
