<?php

namespace App\Http\Resources;

use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One enrolment of one person in one course.
 *
 * @mixin Course
 */
class EnrollmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $course = $this->resource;
        $pivot = $course->pivot;

        return [
            'course' => [
                'id' => $course->id,
                'slug' => $course->slug,
                'title' => $course->title,
            ],
            'role' => $pivot?->role ?? 'student',
            'status' => $pivot?->status ?? 'active',
            'progress' => (int) ($pivot?->progress ?? 0),
            'enrolled_at' => $pivot?->enrolled_at,
            'completed_at' => $pivot?->completed_at,
            'last_accessed_at' => $pivot?->last_accessed_at,
        ];
    }
}
