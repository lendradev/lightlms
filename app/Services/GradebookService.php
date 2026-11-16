<?php

namespace App\Services;

use App\Models\Course;
use App\Models\GradeCategory;
use App\Models\GradeGrade;
use App\Models\GradeItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Computes a course gradebook.
 *
 * The model is a deliberate simplification of Moodle's: grade items are what
 * gets graded, grade categories group them, and a grade grade is one person's
 * mark on one item.
 *
 * The total is computed in two layers, the way Moodle does it: items roll up
 * into their category, then the top level categories and the items without a
 * category roll up into the course total. A category carries its own
 * aggregation, so a course can weight "Exams" heavily and "Participation"
 * lightly without touching the individual items.
 */
class GradebookService
{
    public const AGGREGATIONS = ['weighted_mean', 'mean', 'sum', 'weighted_sum'];

    /**
     * Build the grade items for a course from its graded activities.
     *
     * Activities that can be graded become items; everything else is ignored.
     */
    public function syncItems(Course $course): void
    {
        $activities = DB::table('lessons')
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where('modules.course_id', $course->id)
            ->whereIn('lessons.type', ['assignment', 'quiz'])
            ->orderBy('modules.position')
            ->orderBy('lessons.position')
            ->get(['lessons.id', 'lessons.title', 'lessons.grade_max']);

        $keep = $activities->pluck('id')->all();

        GradeItem::query()
            ->where('course_id', $course->id)
            ->where('source_type', 'activity')
            ->whereNotIn('source_id', $keep === [] ? [0] : $keep)
            ->delete();

        foreach ($activities as $index => $activity) {
            GradeItem::query()->updateOrCreate(
                [
                    'course_id' => $course->id,
                    'source_type' => 'activity',
                    'source_id' => $activity->id,
                ],
                [
                    'name' => $activity->title,
                    'max_points' => $activity->grade_max ?: 100,
                    'sort_order' => $index + 1,
                ],
            );
        }
    }

    /**
     * The categories of a course, in display order.
     *
     * @return Collection<int, GradeCategory>
     */
    public function categories(Course $course): Collection
    {
        return GradeCategory::query()
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Create a category.
     */
    public function saveCategory(
        Course $course,
        string $name,
        ?int $parentId = null,
        string $aggregation = 'weighted_mean',
        ?float $weight = null,
        bool $hidden = false,
    ): GradeCategory {
        $sortOrder = (int) GradeCategory::query()
            ->where('course_id', $course->id)
            ->max('sort_order');

        return GradeCategory::query()->create([
            'course_id' => $course->id,
            'parent_id' => $parentId,
            'name' => $name,
            'aggregation' => $aggregation,
            'weight' => $weight,
            'sort_order' => $sortOrder + 1,
            'hidden' => $hidden,
        ]);
    }

    public function updateCategory(
        GradeCategory $category,
        string $name,
        ?int $parentId,
        string $aggregation,
        ?float $weight,
        bool $hidden,
    ): GradeCategory {
        $category->update([
            'name' => $name,
            'parent_id' => $parentId,
            'aggregation' => $aggregation,
            'weight' => $weight,
            'hidden' => $hidden,
        ]);

        return $category;
    }

    /**
     * Delete a category. Its items fall back to the top level.
     */
    public function deleteCategory(GradeCategory $category): void
    {
        GradeItem::query()
            ->where('category_id', $category->id)
            ->update(['category_id' => null]);

        GradeCategory::query()
            ->where('parent_id', $category->id)
            ->update(['parent_id' => null]);

        $category->delete();
    }

    /**
     * Create an item that is not tied to an activity.
     */
    public function createManualItem(
        Course $course,
        string $name,
        float $maxPoints,
        ?int $categoryId = null,
        ?float $weight = null,
        ?float $passPoints = null,
        ?User $createdBy = null,
    ): GradeItem {
        $sortOrder = (int) GradeItem::query()
            ->where('course_id', $course->id)
            ->max('sort_order');

        return GradeItem::query()->create([
            'course_id' => $course->id,
            'category_id' => $categoryId,
            'source_type' => 'manual',
            'source_id' => null,
            'name' => $name,
            'max_points' => $maxPoints,
            'pass_points' => $passPoints,
            'weight' => $weight,
            'aggregation' => 'weighted_mean',
            'sort_order' => $sortOrder + 1,
            'created_by' => $createdBy?->id,
        ]);
    }

    public function updateItem(
        GradeItem $item,
        string $name,
        ?float $maxPoints,
        ?float $passPoints,
        ?float $weight,
        ?int $categoryId,
        bool $hidden,
        bool $locked,
    ): GradeItem {
        $item->update([
            'name' => $name,
            'max_points' => $maxPoints,
            'pass_points' => $passPoints,
            'weight' => $weight,
            'category_id' => $categoryId,
            'hidden' => $hidden,
            'locked' => $locked,
        ]);

        return $item;
    }

    /**
     * The gradebook as the grader sees it: items down the side, students across.
     *
     * @return array{items: Collection<int, GradeItem>, students: Collection<int, User>, grades: Collection<int, GradeGrade>, categories: Collection<int, GradeCategory>}
     */
    public function graderView(Course $course): array
    {
        $this->syncItems($course);

        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->orderBy('sort_order')
            ->get();

        $students = $course->students()->orderBy('name')->get();

        $grades = GradeGrade::query()
            ->whereIn('item_id', $items->pluck('id'))
            ->whereIn('user_id', $students->pluck('id'))
            ->get()
            ->keyBy(fn (GradeGrade $grade) => $grade->item_id.':'.$grade->user_id);

        return [
            'items' => $items,
            'students' => $students,
            'grades' => $grades,
            'categories' => $this->categories($course),
        ];
    }

    /**
     * The course total for one student.
     *
     * The total is computed over the top level categories and the items without
     * a category, so a category weight and an item weight are never counted
     * twice.
     *
     * @return array{points: float|null, max: float, percent: float|null, graded: int, letter: string|null}
     */
    public function courseTotal(Course $course, User $student): array
    {
        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('locked', false)
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return ['points' => null, 'max' => 0.0, 'percent' => null, 'graded' => 0, 'letter' => null];
        }

        $grades = GradeGrade::query()
            ->whereIn('item_id', $items->pluck('id'))
            ->where('user_id', $student->id)
            ->get()
            ->keyBy(fn (GradeGrade $grade) => (string) $grade->item_id);

        $categories = $this->categories($course);

        // Roll every item up into its category first. Items without a category
        // stay at the top level and are aggregated with the course method.
        $buckets = [];
        $counted = 0;

        foreach ($items as $item) {
            $grade = $grades->get((string) $item->id);

            if ($grade === null || $grade->status === 'excluded' || $grade->final_points === null) {
                continue;
            }

            $counted++;
            $key = $item->category_id === null ? 'course' : (string) $item->category_id;

            $buckets[$key][] = [
                'points' => (float) $grade->final_points,
                'max' => (float) ($item->max_points ?? 0),
                'weight' => (float) ($item->weight ?? 1),
            ];
        }

        // A category rolls up its own items and the items of its children with
        // its own aggregation, then the course rolls up the categories with the
        // course aggregation.
        $entries = [];

        foreach ($categories->whereNull('parent_id') as $category) {
            $children = $buckets[(string) $category->id] ?? [];

            foreach ($categories->where('parent_id', $category->id) as $child) {
                foreach ($buckets[(string) $child->id] ?? [] as $entry) {
                    $children[] = $entry;
                }
            }

            if ($children === []) {
                continue;
            }

            $entries[] = [
                'points' => $this->aggregate($children, $category->aggregation),
                'max' => $this->aggregateMax($children, $category->aggregation),
                'weight' => (float) ($category->weight ?? 1),
            ];
        }

        foreach ($buckets['course'] ?? [] as $entry) {
            $entries[] = $entry;
        }

        $empty = ['points' => null, 'max' => 0.0, 'percent' => null, 'graded' => 0, 'letter' => null];

        if ($entries === []) {
            return $empty;
        }

        $aggregation = $this->courseAggregation($course);
        $points = $this->aggregate($entries, $aggregation);
        $max = $this->aggregateMax($entries, $aggregation);
        $percent = $max > 0 ? round(($points / $max) * 100, 1) : null;

        return [
            'points' => round($points, 2),
            'max' => round($max, 2),
            'percent' => $percent,
            'graded' => $counted,
            'letter' => $percent === null ? null : $this->letterGrade($course, $percent),
        ];
    }

    /**
     * The total of one category for one student.
     *
     * @return array{points: float|null, max: float, percent: float|null, graded: int}
     */
    public function categoryTotal(Course $course, GradeCategory $category, User $student): array
    {
        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('category_id', $category->id)
            ->where('locked', false)
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return ['points' => null, 'max' => 0.0, 'percent' => null, 'graded' => 0];
        }

        $grades = GradeGrade::query()
            ->whereIn('item_id', $items->pluck('id'))
            ->where('user_id', $student->id)
            ->get()
            ->keyBy(fn (GradeGrade $grade) => (string) $grade->item_id);

        $entries = [];

        foreach ($items as $item) {
            $grade = $grades->get((string) $item->id);

            if ($grade === null || $grade->status === 'excluded' || $grade->final_points === null) {
                continue;
            }

            $entries[] = [
                'points' => (float) $grade->final_points,
                'max' => (float) ($item->max_points ?? 0),
                'weight' => (float) ($item->weight ?? 1),
            ];
        }

        if ($entries === []) {
            return ['points' => null, 'max' => 0.0, 'percent' => null, 'graded' => 0];
        }

        $points = $this->aggregate($entries, $category->aggregation);
        $max = $this->aggregateMax($entries, $category->aggregation);
        $percent = $max > 0 ? round(($points / $max) * 100, 1) : null;

        return [
            'points' => round($points, 2),
            'max' => round($max, 2),
            'percent' => $percent,
            'graded' => count($entries),
        ];
    }

    /**
     * The gradebook as the student sees it: one row per item plus a total.
     *
     * @return array{items: Collection<int, GradeItem>, grades: Collection<int, GradeGrade>, total: array<string, mixed>, categories: Collection<int, GradeCategory>, categoryTotals: array<int, array<string, mixed>>}
     */
    public function reportView(Course $course, User $student): array
    {
        $this->syncItems($course);

        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('hidden', false)
            ->orderBy('sort_order')
            ->get();

        $grades = GradeGrade::query()
            ->whereIn('item_id', $items->pluck('id'))
            ->where('user_id', $student->id)
            ->get()
            ->keyBy(fn (GradeGrade $grade) => (string) $grade->item_id);

        $categories = $this->categories($course)->whereNull('parent_id');

        return [
            'items' => $items,
            'grades' => $grades,
            'total' => $this->courseTotal($course, $student),
            'categories' => $categories,
            'categoryTotals' => $categories
                ->mapWithKeys(fn (GradeCategory $category) => [
                    $category->id => $this->categoryTotal($course, $category, $student),
                ])
                ->all(),
        ];
    }

    /**
     * Save a grade. An override is kept as entered and is never recomputed.
     *
     * A null grader means the mark came from an automatic source, such as a
     * scored quiz attempt.
     */
    public function saveGrade(
        GradeItem $item,
        User $student,
        ?float $points,
        ?string $feedback,
        ?User $grader = null,
        bool $override = false,
    ): GradeGrade {
        $grade = GradeGrade::query()->updateOrCreate(
            ['item_id' => $item->id, 'user_id' => $student->id],
            [
                'raw_points' => $points,
                'final_points' => $points,
                'feedback' => $feedback,
                'status' => $points === null ? 'pending' : 'graded',
                'source' => $grader === null ? 'auto' : 'manual',
                'graded_by' => $grader?->id,
                'graded_at' => now(),
                'is_overridden' => $override,
                'overridden_by' => $override ? $grader?->id : null,
                'overridden_at' => $override ? now() : null,
            ],
        );

        return $grade;
    }

    /**
     * The aggregation a course uses for its own total.
     */
    public function courseAggregation(Course $course): string
    {
        $value = DB::table('grade_settings')
            ->where('course_id', $course->id)
            ->where('name', 'aggregation')
            ->value('value');

        return in_array($value, self::AGGREGATIONS, true) ? $value : 'weighted_mean';
    }

    /**
     * The letter grade for a percentage, following the course thresholds.
     *
     * The thresholds are stored as `letter_a`, `letter_b` and so on, each
     * holding the lowest percentage that still earns that letter.
     */
    public function letterGrade(Course $course, float $percent): ?string
    {
        $settings = DB::table('grade_settings')
            ->where('course_id', $course->id)
            ->whereIn('name', ['letter_a', 'letter_b', 'letter_c', 'letter_d'])
            ->pluck('value', 'name');

        $thresholds = [
            'A' => (float) ($settings['letter_a'] ?? 90),
            'B' => (float) ($settings['letter_b'] ?? 80),
            'C' => (float) ($settings['letter_c'] ?? 70),
            'D' => (float) ($settings['letter_d'] ?? 60),
        ];

        foreach ($thresholds as $letter => $threshold) {
            if ($percent >= $threshold) {
                return $letter;
            }
        }

        return 'E';
    }

    /**
     * Roll a set of entries up with one aggregation method.
     *
     * @param  list<array{points: float, max: float, weight: float}>  $entries
     */
    private function aggregate(array $entries, string $method): float
    {
        $totalWeight = array_sum(array_column($entries, 'weight'));

        return match ($method) {
            'mean' => array_sum(array_column($entries, 'points')) / count($entries),
            'sum' => array_sum(array_column($entries, 'points')),
            'weighted_sum' => $this->weightedSum($entries),
            default => $totalWeight > 0 ? $this->weightedSum($entries) / $totalWeight : 0.0,
        };
    }

    /**
     * The maximum that matches the aggregation, so a percentage compares like
     * with like.
     *
     * @param  list<array{points: float, max: float, weight: float}>  $entries
     */
    private function aggregateMax(array $entries, string $method): float
    {
        if ($entries === []) {
            return 0.0;
        }

        $totalWeight = array_sum(array_column($entries, 'weight'));

        return match ($method) {
            'sum' => array_sum(array_column($entries, 'max')),
            'weighted_sum' => $this->weightedSum(array_map(
                fn (array $entry) => [
                    'points' => $entry['max'],
                    'max' => $entry['max'],
                    'weight' => $entry['weight'],
                ],
                $entries,
            )),
            'mean' => array_sum(array_column($entries, 'max')) / count($entries),
            default => $totalWeight > 0
                ? $this->weightedSum(array_map(
                    fn (array $entry) => [
                        'points' => $entry['max'],
                        'max' => $entry['max'],
                        'weight' => $entry['weight'],
                    ],
                    $entries,
                )) / $totalWeight
                : 0.0,
        };
    }

    /**
     * @param  list<array{points: float, max: float, weight: float}>  $entries
     */
    private function weightedSum(array $entries): float
    {
        $sum = 0.0;

        foreach ($entries as $entry) {
            $sum += $entry['points'] * $entry['weight'];
        }

        return $sum;
    }
}
