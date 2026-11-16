<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A person, as the API exposes them.
 *
 * The email address is only included for the people the caller is allowed to
 * see: themselves, and the students of a course they teach.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($this->includesEmail($request), $this->email),
            'role' => $this->role->value,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * The email address travels with a record only when the caller may read
     * it. Everywhere else it is omitted rather than blanked, so the shape of
     * the response never suggests the address is simply empty.
     */
    private function includesEmail(Request $request): bool
    {
        $caller = $request->user();

        if ($caller === null) {
            return false;
        }

        return $caller->id === $this->id || $caller->isAdmin();
    }
}
