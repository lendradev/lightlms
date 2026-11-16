<?php

namespace App\Http\Controllers\Teaching;

use App\Enums\CourseRole;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\User;
use App\Services\AuditService;
use App\Services\EnrollmentService;
use App\Services\PermissionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RosterController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions,
        private readonly EnrollmentService $enrollments,
        private readonly AuditService $audit,
    ) {}

    public function index(Request $request, Course $course): Response
    {
        abort_unless($this->permissions->allows($request->user(), 'course.viewparticipants', $course), 403);

        return Inertia::render('Teaching/Roster', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'self_enroll' => $course->self_enroll,
                'capacity' => $course->capacity,
            ],
            'members' => $course->students()
                ->withPivot(['status', 'progress', 'role', 'enrolled_at', 'last_accessed_at'])
                ->orderBy('name')
                ->get()
                ->map(fn (User $member) => [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->pivot->role,
                    'status' => $member->pivot->status,
                    'progress' => (int) $member->pivot->progress,
                    'enrolled_at' => $member->pivot->enrolled_at,
                ]),
            'roles' => array_map(
                fn (CourseRole $role) => ['value' => $role->value, 'label' => $role->label()],
                CourseRole::cases(),
            ),
        ]);
    }

    public function store(Request $request, Course $course): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.manageactivities', $course), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:student,assistant,instructor'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null) {
            return back()->with('error', 'No account exists for that email. Create the user first.');
        }

        $this->enrollments->assignRole($course, $user, CourseRole::from($validated['role']));

        $this->audit->record('roster.assign', $course, Course::class, $course->id, $course->id, [], [
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);

        return back()->with('success', "{$user->name} added to the course.");
    }

    public function update(Request $request, Course $course, User $user): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.manageactivities', $course), 403);

        $validated = $request->validate([
            'role' => ['required', 'in:student,assistant,instructor'],
        ]);

        $this->enrollments->assignRole($course, $user, CourseRole::from($validated['role']));

        $this->audit->record('roster.update', $course, Course::class, $course->id, $course->id, [], [
            'user_id' => $user->id,
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Role updated.');
    }

    public function destroy(Request $request, Course $course, User $user): RedirectResponse
    {
        abort_unless($this->permissions->allows($request->user(), 'course.manageactivities', $course), 403);

        $this->enrollments->unenroll($user->id, $course->id);

        $this->audit->record('roster.remove', $course, Course::class, $course->id, $course->id, [], [
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Removed from the course.');
    }
}
