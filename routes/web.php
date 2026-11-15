<?php

use App\Models\Course;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $courses = Course::query()
        ->published()
        ->withCount('modules')
        ->orderBy('published_at', 'desc')
        ->limit(6)
        ->get(['id', 'title', 'slug', 'summary', 'instructor_name', 'level', 'duration_minutes']);

    return inertia('Welcome', [
        'courses' => $courses,
    ]);
})->name('home');

Route::get('/courses', function () {
    $courses = Course::query()
        ->published()
        ->withCount('modules')
        ->orderBy('published_at', 'desc')
        ->paginate(12);

    return inertia('Courses/Index', [
        'courses' => $courses,
    ]);
})->name('courses.index');

Route::get('/courses/{course}', function (Course $course) {
    abort_unless($course->isPublished(), 404);

    $course->load('modules.lessons');

    return inertia('Courses/Show', [
        'course' => $course,
    ]);
})->name('courses.show');
