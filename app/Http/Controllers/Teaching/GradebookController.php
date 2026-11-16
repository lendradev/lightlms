<?php

namespace App\Http\Controllers\Teaching;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\GradeItem;
use App\Models\User;
use App\Services\AuditService;
use App\Services\GradebookService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function export(Request $request, Course $course): \Illuminate\Http\Response
    {
        abort_unless($this->permissions->allows($request->user(), 'grade.export', $course), 403);

        $view = $this->gradebook->graderView($course);

        $rows = [];
        $rows[] = implode(',', array_merge(
            ['Student', 'Email'],
            $view['items']->map(fn (GradeItem $item) => $item->name)->all(),
            ['Total'],
        ));

        foreach ($view['students'] as $student) {
            $cells = [$student->name, $student->email];

            foreach ($view['items'] as $item) {
                $grade = $view['grades']->get($item->id.':'.$student->id);
                $cells[] = $grade?->final_points === null ? '' : (string) (float) $grade->final_points;
            }

            $total = $this->gradebook->courseTotal($course, $student);
            $cells[] = $total['points'] === null ? '' : (string) $total['points'];

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
}
