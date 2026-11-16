<?php

namespace App\Http\Resources;

use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One attempt of one learner on one assignment.
 *
 * @mixin Submission
 */
class SubmissionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'assignment_id' => $this->assignment_id,
            'user_id' => $this->user_id,
            'content' => $this->content,
            'score' => $this->score === null ? null : (float) $this->score,
            'feedback' => $this->feedback,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'graded_at' => $this->graded_at?->toIso8601String(),
            'file' => $this->file === null ? null : [
                'id' => $this->file->id,
                'name' => $this->file->original_name,
                'mime_type' => $this->file->mime_type,
                'size' => $this->file->size,
                'url' => route('files.show', ['file' => $this->file->id]),
            ],
        ];
    }
}
