<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function __construct(
        protected readonly NotificationService $notifications,
    ) {}

    /**
     * The inbox of the person the token belongs to.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $limit = (int) $request->integer('limit', 50);

        return NotificationResource::collection($this->notifications->inbox($request->user(), min($limit, 200)));
    }

    /**
     * Mark one notification as read.
     */
    public function read(Request $request, int $notification): NotificationResource
    {
        $item = $request->user()->notifications()->findOrFail($notification);

        $this->notifications->markRead($request->user(), $item);

        return new NotificationResource($item->refresh());
    }
}
