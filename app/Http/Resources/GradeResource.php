<?php

namespace App\Http\Resources;

use App\Models\GradeGrade;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One mark of one person on one grade item.
 *
 * @mixin GradeGrade
 */
class GradeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'user_id' => $this->user_id,
            'final_points' => $this->final_points === null ? null : (float) $this->final_points,
            'feedback' => $this->feedback,
            'status' => $this->status,
            'source' => $this->source,
            'graded_at' => $this->graded_at?->toIso8601String(),
        ];
    }
}
