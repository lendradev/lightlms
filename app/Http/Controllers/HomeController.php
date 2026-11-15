<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $courses = Course::query()
            ->published()
            ->withCount('modules')
            ->orderBy('published_at', 'desc')
            ->limit(3)
            ->get();

        return Inertia::render('Welcome', [
            'courses' => $courses,
        ]);
    }
}
