<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Records every privileged action.
 *
 * Reads count too: under FERPA an instructor may only look at a student's
 * record because of their teaching role, so access has to be traceable.
 */
class AuditService
{
    public function record(
        string $event,
        ?object $subject = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?int $courseId = null,
        array $oldValues = [],
        array $newValues = [],
        ?Request $request = null,
        ?User $actor = null,
    ): void {
        $request ??= request();
        $actor ??= $request->user() ?? auth()->user();

        try {
            AuditLog::query()->create([
                'actor_id' => $actor?->id,
                'actor_role' => $actor?->role?->value,
                'event' => $event,
                'subject_type' => $subjectType ?? ($subject === null ? null : $subject::class),
                'subject_id' => $subjectId ?? ($subject === null ? null : $subject->getKey()),
                'course_id' => $courseId,
                'old_values' => $oldValues === [] ? null : $oldValues,
                'new_values' => $newValues === [] ? null : $newValues,
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
            ]);
        } catch (\Throwable $exception) {
            // Auditing must never take the site down.
            Log::warning('Audit write failed: '.$exception->getMessage());
        }
    }
}
