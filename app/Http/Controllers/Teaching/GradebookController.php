<?php

namespace App\Http\Controllers\Teaching;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GradeCategory;
use App\Models\GradeItem;
use App\Models\User;
use App\Services\AuditService;
use App\Services\GradebookService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GradebookController extends Controller
{
    public function __construct(
        private readonly GradebookService $gradebook,
        private readonly PermissionService $permissions,
        private readonly AuditService $audit,
    ) {}

    public function show(Request $request, Course $course): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'course.viewcoursegrades', $course), 403);

        $view = $this->gradebook->graderView($course);

        return Inertia::render('Teaching/Gradebook', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'items' => $view['items']->map(fn (GradeItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'max_points' => (float) ($item->max_points ?? 0),
                'weight' => $item->weight === null ? null : (float) $item->weight,
                'sort_order' => $item->sort_order,
                'category_id' => $item->category_id,
                'source_type' => $item->source_type,
                'hidden' => (bool) $item->hidden,
                'locked' => (bool) $item->locked,
            ]),
            'categories' => $view['categories']->map(fn (GradeCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'aggregation' => $category->aggregation,
                'weight' => $category->weight === null ? null : (float) $category->weight,
                'sort_order' => $category->sort_order,
                'hidden' => (bool) $category->hidden,
            ]),
            'students' => $view['students']->map(fn (User $student) => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ]),
            'grades' => $view['grades']->values()->map(fn ($grade) => [
                'item_id' => $grade->item_id,
                'user_id' => $grade->user_id,
                'final_points' => $grade->final_points === null ? null : (float) $grade->final_points,
                'status' => $grade->status,
                'is_overridden' => $grade->is_overridden,
            ]),
            'totals' => $view['students']->mapWithKeys(fn (User $student) => [
                $student->id => $this->gradebook->courseTotal($course, $student),
            ]),
            'categoryTotals' => $view['students']->mapWithKeys(fn (User $student) => [
                $student->id => $view['categories']
                    ->whereNull('parent_id')
                    ->mapWithKeys(fn (GradeCategory $category) => [
                        $category->id => $this->gradebook->categoryTotal($course, $category, $student),
                    ])
                    ->all(),
            ]),
        ]);
    }

    /**
     * Categories, manual items and the course settings.
     */
    public function settings(Request $request, Course $course): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);

        $view = $this->gradebook->graderView($course);

        $settings = DB::table('grade_settings')
            ->where('course_id', $course->id)
            ->pluck('value', 'name');

        return Inertia::render('Teaching/GradebookSettings', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'categories' => $view['categories']->map(fn (GradeCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'parent_id' => $category->parent_id,
                'aggregation' => $category->aggregation,
                'weight' => $category->weight === null ? null : (float) $category->weight,
                'sort_order' => $category->sort_order,
                'hidden' => (bool) $category->hidden,
            ]),
            'items' => $view['items']->map(fn (GradeItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'max_points' => (float) ($item->max_points ?? 0),
                'weight' => $item->weight === null ? null : (float) $item->weight,
                'category_id' => $item->category_id,
                'source_type' => $item->source_type,
                'hidden' => (bool) $item->hidden,
                'locked' => (bool) $item->locked,
            ]),
            'settings' => [
                'aggregation' => $this->gradebook->courseAggregation($course),
                'letter_a' => isset($settings['letter_a']) ? (float) $settings['letter_a'] : null,
                'letter_b' => isset($settings['letter_b']) ? (float) $settings['letter_b'] : null,
                'letter_c' => isset($settings['letter_c']) ? (float) $settings['letter_c'] : null,
                'letter_d' => isset($settings['letter_d']) ? (float) $settings['letter_d'] : null,
            ],
            'aggregations' => collect(GradebookService::AGGREGATIONS)->map(fn (string $method) => [
                'value' => $method,
                'label' => ucfirst(str_replace('_', ' ', $method)),
            ])->all(),
        ]);
    }

    public function store(Request $request, Course $course, GradeItem $item): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);
        abort_unless($item->course_id === $course->id, 404);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'points' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $student = User::query()->findOrFail($validated['user_id']);

        $previous = $item->grades()->where('user_id', $student->id)->first();

        $this->gradebook->saveGrade(
            $item,
            $student,
            $validated['points'] === null ? null : (float) $validated['points'],
            $validated['feedback'] ?? null,
            $request->user(),
            $previous?->is_overridden ?? false,
        );

        $this->audit->record(
            'grade.update',
            $item,
            'App\\Models\\GradeItem',
            $item->id,
            $course->id,
            ['points' => $previous?->final_points === null ? null : (float) $previous->final_points],
            ['points' => $validated['points'] === null ? null : (float) $validated['points']],
        );

        return back()->with('success', 'Grade saved.');
    }

    /**
     * The gradebook as the learner sees it.
     */
    public function report(Request $request, Course $course): Response
    {
        $user = $request->user();

        abort_unless(
            $this->permissions->allows($user, 'grade.viewown', $course),
            403,
        );

        $view = $this->gradebook->reportView($course, $user);

        return Inertia::render('Learning/Gradebook', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
            ],
            'items' => $view['items']->map(fn (GradeItem $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'max_points' => (float) ($item->max_points ?? 0),
                'category_id' => $item->category_id,
                'sort_order' => $item->sort_order,
            ]),
            'categories' => $view['categories']->map(fn (GradeCategory $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'weight' => $category->weight === null ? null : (float) $category->weight,
            ]),
            'grades' => $view['grades']->values()->map(fn ($grade) => [
                'item_id' => $grade->item_id,
                'final_points' => $grade->final_points === null ? null : (float) $grade->final_points,
                'feedback' => $grade->feedback,
                'status' => $grade->status,
            ]),
            'categoryTotals' => $view['categoryTotals'],
            'total' => $view['total'],
        ]);
    }

    public function export(Request $request, Course $course): \Illuminate\Http\Response
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.export', $course), 403);

        $view = $this->gradebook->graderView($course);

        $rows = [];
        $rows[] = implode(',', array_merge(
            ['Student', 'Email'],
            $view['items']->map(fn (GradeItem $item) => $item->name)->all(),
            ['Total', 'Percent', 'Letter'],
        ));

        foreach ($view['students'] as $student) {
            $cells = [$student->name, $student->email];

            foreach ($view['items'] as $item) {
                $grade = $view['grades']->get($item->id.':'.$student->id);
                $cells[] = $grade?->final_points === null ? '' : (string) (float) $grade->final_points;
            }

            $total = $this->gradebook->courseTotal($course, $student);
            $cells[] = $total['points'] === null ? '' : (string) $total['points'];
            $cells[] = $total['percent'] === null ? '' : (string) $total['percent'];
            $cells[] = $total['letter'] ?? '';

            $rows[] = implode(',', array_map(
                fn (string $cell) => '"'.str_replace('"', '""', $cell).'"',
                $cells,
            ));
        }

        $csv = implode("\n", $rows)."\n";

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$course->slug.'-grades.csv"',
        ]);
    }

    /**
     * Create a category.
     */
    public function storeCategory(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:grade_categories,id'],
            'aggregation' => ['required', 'in:'.implode(',', GradebookService::AGGREGATIONS)],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'hidden' => ['nullable', 'boolean'],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId !== null) {
            abort_unless(
                GradeCategory::query()
                    ->where('course_id', $course->id)
                    ->where('id', $parentId)
                    ->exists(),
                404,
            );
        }

        $category = $this->gradebook->saveCategory(
            $course,
            $validated['name'],
            $parentId,
            $validated['aggregation'],
            $validated['weight'] === null ? null : (float) $validated['weight'],
            (bool) ($validated['hidden'] ?? false),
        );

        $this->audit->record(
            'gradebook.category.create',
            $category,
            'App\\Models\\GradeCategory',
            $category->id,
            $course->id,
            [],
            ['name' => $category->name, 'aggregation' => $category->aggregation],
        );

        return back()->with('success', 'Category created.');
    }

    public function updateCategory(
        Request $request,
        Course $course,
        GradeCategory $category,
    ): RedirectResponse {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);
        abort_unless($category->course_id === $course->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'parent_id' => ['nullable', 'integer', 'exists:grade_categories,id'],
            'aggregation' => ['required', 'in:'.implode(',', GradebookService::AGGREGATIONS)],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'hidden' => ['nullable', 'boolean'],
        ]);

        $parentId = $validated['parent_id'] ?? null;

        if ($parentId !== null) {
            abort_unless(
                GradeCategory::query()
                    ->where('course_id', $course->id)
                    ->where('id', $parentId)
                    ->exists(),
                404,
            );
            abort_unless((int) $parentId !== (int) $category->id, 422);
        }

        $this->gradebook->updateCategory(
            $category,
            $validated['name'],
            $parentId,
            $validated['aggregation'],
            $validated['weight'] === null ? null : (float) $validated['weight'],
            (bool) ($validated['hidden'] ?? false),
        );

        $this->audit->record(
            'gradebook.category.update',
            $category,
            'App\\Models\\GradeCategory',
            $category->id,
            $course->id,
            ['name' => $category->getOriginal('name')],
            ['name' => $category->name, 'aggregation' => $category->aggregation],
        );

        return back()->with('success', 'Category updated.');
    }

    public function destroyCategory(
        Request $request,
        Course $course,
        GradeCategory $category,
    ): RedirectResponse {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);
        abort_unless($category->course_id === $course->id, 404);

        $this->gradebook->deleteCategory($category);

        $this->audit->record(
            'gradebook.category.delete',
            $category,
            'App\\Models\\GradeCategory',
            $category->id,
            $course->id,
            ['name' => $category->name],
            [],
        );

        return back()->with('success', 'Category deleted.');
    }

    /**
     * Create an item that is not tied to an activity.
     */
    public function storeManualItem(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'max_points' => ['required', 'numeric', 'min:0', 'max:100000'],
            'pass_points' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'weight' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'category_id' => ['nullable', 'integer', 'exists:grade_categories,id'],
        ]);

        $categoryId = $validated['category_id'] ?? null;

        if ($categoryId !== null) {
            abort_unless(
                GradeCategory::query()
                    ->where('course_id', $course->id)
                    ->where('id', $categoryId)
                    ->exists(),
                404,
            );
        }

        $item = $this->gradebook->createManualItem(
            $course,
            $validated['name'],
            (float) $validated['max_points'],
            $categoryId,
            array_key_exists('weight', $validated) && $validated['weight'] !== null
                ? (float) $validated['weight']
                : null,
            array_key_exists('pass_points', $validated) && $validated['pass_points'] !== null
                ? (float) $validated['pass_points']
                : null,
            $request->user(),
        );

        $this->audit->record(
            'gradebook.item.create',
            $item,
            'App\\Models\\GradeItem',
            $item->id,
            $course->id,
            [],
            ['name' => $item->name, 'max_points' => (float) $item->max_points],
        );

        return back()->with('success', 'Item created.');
    }

    /**
     * Change the weight, the maximum or the visibility of an item.
     */
    public function updateItem(Request $request, Course $course, GradeItem $item): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);
        abort_unless($item->course_id === $course->id, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'max_points' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'pass_points' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100000'],
            'weight' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:1000'],
            'category_id' => ['sometimes', 'nullable', 'integer', 'exists:grade_categories,id'],
            'hidden' => ['sometimes', 'boolean'],
            'locked' => ['sometimes', 'boolean'],
        ]);

        $categoryId = array_key_exists('category_id', $validated)
            ? $validated['category_id']
            : $item->category_id;

        if ($categoryId !== null) {
            abort_unless(
                GradeCategory::query()
                    ->where('course_id', $course->id)
                    ->where('id', $categoryId)
                    ->exists(),
                404,
            );
        }

        $this->gradebook->updateItem(
            $item,
            $validated['name'] ?? $item->name,
            array_key_exists('max_points', $validated)
                ? ($validated['max_points'] === null ? null : (float) $validated['max_points'])
                : ($item->max_points === null ? null : (float) $item->max_points),
            array_key_exists('pass_points', $validated)
                ? ($validated['pass_points'] === null ? null : (float) $validated['pass_points'])
                : ($item->pass_points === null ? null : (float) $item->pass_points),
            array_key_exists('weight', $validated)
                ? ($validated['weight'] === null ? null : (float) $validated['weight'])
                : ($item->weight === null ? null : (float) $item->weight),
            $categoryId,
            (bool) ($validated['hidden'] ?? $item->hidden),
            (bool) ($validated['locked'] ?? $item->locked),
        );

        $this->audit->record(
            'gradebook.item.update',
            $item,
            'App\\Models\\GradeItem',
            $item->id,
            $course->id,
            ['weight' => $item->getOriginal('weight')],
            ['weight' => $item->weight],
        );

        return back()->with('success', 'Item updated.');
    }

    /**
     * Change how the course total is aggregated and where the letter grades
     * start.
     */
    public function updateSettings(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.edit', $course), 403);

        $validated = $request->validate([
            'aggregation' => ['required', 'in:'.implode(',', GradebookService::AGGREGATIONS)],
            'letter_a' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'letter_b' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'letter_c' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'letter_d' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        DB::table('grade_settings')->updateOrInsert(
            ['course_id' => $course->id, 'name' => 'aggregation'],
            ['value' => $validated['aggregation'], 'created_at' => now(), 'updated_at' => now()],
        );

        foreach (['letter_a', 'letter_b', 'letter_c', 'letter_d'] as $name) {
            DB::table('grade_settings')->updateOrInsert(
                ['course_id' => $course->id, 'name' => $name],
                [
                    'value' => $validated[$name] === null ? null : (string) $validated[$name],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }

        $this->audit->record(
            'gradebook.settings.update',
            $course,
            'App\\Models\\Course',
            $course->id,
            $course->id,
            [],
            ['aggregation' => $validated['aggregation']],
        );

        return back()->with('success', 'Gradebook settings saved.');
    }
}
