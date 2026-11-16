<?php

namespace App\Http\Controllers;

use App\Models\FileAsset;
use App\Models\Submission;
use App\Services\PermissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Hands a stored file to the person allowed to see it.
 *
 * The route never exposes the path on disk, and the file is only served when
 * the requester owns the submission or may read every hand-in in the course.
 */
class FileController extends Controller
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function show(Request $request, FileAsset $file): StreamedResponse
    {
        $submission = Submission::query()->where('file_id', $file->id)->first();

        if ($submission !== null) {
            $course = $submission->assignment?->lesson?->module?->course;

            abort_if($course === null, 404);

            $isOwner = $submission->user_id === $request->user()->id;

            abort_unless(
                $isOwner || $this->permissions->allows($request->user(), 'submission.viewall', $course),
                403,
                'You do not have access to this file.',
            );
        } else {
            $course = $file->lesson?->module?->course;

            abort_if($course === null, 404);

            abort_unless(
                $this->permissions->allows($request->user(), 'activity.view', $course),
                403,
                'You do not have access to this file.',
            );
        }

        abort_unless(
            Storage::disk($file->disk)->exists($file->path),
            404,
            'This file is no longer available.',
        );

        return Storage::disk($file->disk)->download(
            $file->path,
            $file->original_name,
            ['Content-Type' => $file->mime_type ?: 'application/octet-stream'],
        );
    }
}
