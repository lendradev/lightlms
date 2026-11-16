<?php

namespace App\Services;

use App\Models\Course;
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
 * mark on one item. Only `mean` and `weighted_mean` aggregation are supported;
 * the rest of Moodle's nine methods add surface without changing what an
 * instructor actually needs.
 */
class GradebookService
{
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

        $existing = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('source_type', 'activity')
            ->pluck('source_id')
            ->all();

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

        unset($existing);
    }

    /**
     * The gradebook as the grader sees it: items down the side, students across.
     *
     * @return array{items: Collection<int, GradeItem>, students: Collection<int, User>, grades: Collection<int, GradeGrade>}
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
        ];
    }

    /**
     * The course total for one student.
     *
     * Weighted mean is the default: every item contributes in proportion to
     * its weight, and items without an explicit weight share the remainder
     * evenly.
     */
    public function courseTotal(Course $course, User $student): array
    {
        $items = GradeItem::query()
            ->where('course_id', $course->id)
            ->where('locked', false)
            ->orderBy('sort_order')
            ->get();

        if ($items->isEmpty()) {
            return ['points' => null, 'max' => 0.0, 'percent' => null];
        }

        $grades = GradeGrade::query()
            ->whereIn('item_id', $items->pluck('id'))
            ->where('user_id', $student->id)
            ->get()
            ->keyBy(fn (GradeGrade $grade) => (string) $grade->item_id);
        $weighted = 0.0;
        $weightedMax = 0.0;
        $totalWeight = 0.0;
        $graded = 0;

        foreach ($items as $item) {
            $grade = $grades->get((string) $item->id);

            if ($grade === null || $grade->status === 'excluded' || $grade->final_points === null) {
                continue;
            }

            $weight = (float) ($item->weight ?? 1);
            $weighted += (float) $grade->final_points * $weight;
            // The maximum has to follow the same weighting, otherwise the
            // percentage compares a weighted mean against a plain sum.
            $weightedMax += (float) ($item->max_points ?? 0) * $weight;
            $totalWeight += $weight;
            $graded++;
        }

        if ($totalWeight === 0.0) {
            return ['points' => null, 'max' => 0.0, 'percent' => null, 'graded' => 0];
        }

        $points = $weighted / $totalWeight;
        $max = $weightedMax / $totalWeight;

        return [
            'points' => round($points, 2),
            'max' => round($max, 2),
            'percent' => $max > 0 ? round(($points / $max) * 100, 1) : null,
            'graded' => $graded,
        ];
    }

    /**
     * The gradebook as the student sees it: one row per item plus a total.
     *
     * @return array{items: Collection<int, GradeItem>, grades: Collection<int, GradeGrade>, total: array<string, mixed>}
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

        return [
            'items' => $items,
            'grades' => $grades,
            'total' => $this->courseTotal($course, $student),
        ];
    }

    /**
     * Save a grade. An override is kept as entered and is never recomputed.
     */
    public function saveGrade(
        GradeItem $item,
        User $student,
        ?float $points,
        ?string $feedback,
        User $grader,
        bool $override = false,
    ): GradeGrade {
        $grade = GradeGrade::query()->updateOrCreate(
            ['item_id' => $item->id, 'user_id' => $student->id],
            [
                'raw_points' => $points,
                'final_points' => $points,
                'feedback' => $feedback,
                'status' => $points === null ? 'pending' : 'graded',
                'source' => 'manual',
                'graded_by' => $grader->id,
                'graded_at' => now(),
                'is_overridden' => $override,
                'overridden_by' => $override ? $grader->id : null,
                'overridden_at' => $override ? now() : null,
            ],
        );

        return $grade;
    }
}
