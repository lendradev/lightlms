<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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
            'courses' => $this->withPlainLabels($courses),
            'filters' => [
                'q' => $request->string('q')->toString(),
                'level' => $request->string('level')->toString(),
            ],
            'levels' => ['beginner', 'intermediate', 'advanced'],
        ]);
    }

    /**
     * Laravel's paginator labels carry HTML entities such as `&laquo;`. They
     * travel as plain text through JSON, so decode them once on the server.
     *
     * @param  LengthAwarePaginator<int, Course>  $paginator
     * @return array<string, mixed>
     */
    private function withPlainLabels(LengthAwarePaginator $paginator): array
    {
        $data = $paginator->toArray();

        $data['links'] = array_map(static function (array $link): array {
            $link['label'] = html_entity_decode((string) $link['label']);

            return $link;
        }, $data['links']);

        return $data;
    }

    public function show(Request $request, Course $course): Response
    {
        abort_unless($course->isPublished(), 404);

        // A module the author hid stays hidden, on the public page as much as
        // in the course itself.
        $course->load(['modules' => fn ($query) => $query->where('visible', true), 'modules.lessons']);

        return Inertia::render('Courses/Show', [
            'course' => $course,
        ]);
    }
}
