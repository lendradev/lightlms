<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeachingController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $courses = Course::query()
            ->ownedBy($user)
            ->withCount(['modules', 'students'])
            ->orderByDesc('updated_at')
            ->get();

        return Inertia::render('Teaching/Index', [
            'courses' => $courses,
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        $this->authorizeCourse($request, $course);

        $course->load('modules.lessons');

        return Inertia::render('Teaching/Course', [
            'course' => $course,
            'students' => $course->students()->withPivot(['status', 'progress', 'enrolled_at'])->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['required', 'string', 'max:8'],
            'self_enroll' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        Course::query()->create([
            'title' => $validated['title'],
            'slug' => Str::slug($validated['title']).'-'.uniqid(),
            'summary' => $validated['summary'] ?? '',
            'description' => $validated['description'] ?? null,
            'level' => $validated['level'],
            'language' => $validated['language'],
            'instructor_name' => $user->name,
            'user_id' => $user->id,
            'status' => 'draft',
            'self_enroll' => (bool) ($validated['self_enroll'] ?? true),
        ]);

        return back()->with('success', 'Course created as a draft.');
    }

    public function update(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request, $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'level' => ['required', 'in:beginner,intermediate,advanced'],
            'language' => ['required', 'string', 'max:8'],
        ]);

        $course->update($validated);

        return back()->with('success', 'Course updated.');
    }

    public function publish(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request, $course);

        $course->update([
            'status' => $course->status === 'published' ? 'draft' : 'published',
            'published_at' => $course->status === 'published' ? null : now(),
        ]);

        return back()->with('success', $course->status === 'published' ? 'Course published.' : 'Course unpublished.');
    }

    public function storeModule(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request, $course);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['nullable', 'string', 'max:500'],
        ]);

        $course->modules()->create([
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'position' => ((int) $course->modules()->max('position')) + 1,
        ]);

        return back()->with('success', 'Section added.');
    }

    public function storeLesson(Request $request, Course $course): RedirectResponse
    {
        $this->authorizeCourse($request, $course);

        $validated = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'title' => ['required', 'string', 'max:180'],
            'type' => ['required', 'in:reading,video,quiz,assignment,forum'],
            'body' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:0', 'max:600'],
            'is_preview' => ['nullable', 'boolean'],
        ]);

        $module = $course->modules()->whereKey($validated['module_id'])->firstOrFail();

        $module->lessons()->create([
            'title' => $validated['title'],
            'type' => $validated['type'],
            'body' => $validated['body'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? 0,
            'is_preview' => (bool) ($validated['is_preview'] ?? false),
            'position' => ((int) $module->lessons()->max('position')) + 1,
        ]);

        return back()->with('success', 'Activity added.');
    }

    private function authorizeCourse(Request $request, Course $course): void
    {
        abort_unless(
            $course->user_id === $request->user()->id || $request->user()->isAdmin(),
            403,
            'You do not manage this course.',
        );
    }
}
