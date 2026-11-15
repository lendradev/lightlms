<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CourseController extends Controller
{
    public function index(Request $request): Response
    {
        $courses = Course::query()
            ->published()
            ->withCount('modules')
            ->search($request->string('q')->toString())
            ->when($request->string('level')->toString() !== '', fn ($query) => $query->where('level', $request->string('level')->toString()))
            ->orderBy('published_at', 'desc')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Courses/Index', [
            'courses' => $courses,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'level' => $request->string('level')->toString(),
            ],
            'levels' => ['beginner', 'intermediate', 'advanced'],
        ]);
    }

    public function show(Request $request, Course $course): Response
    {
        abort_unless($course->isPublished(), 404);

        $course->load('modules.lessons');

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }
}
