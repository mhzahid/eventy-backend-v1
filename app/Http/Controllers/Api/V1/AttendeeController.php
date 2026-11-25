<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\AttendeeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendeeController extends Controller
{
    public function index(Request $request, string $eventId): JsonResponse
    {
        $request->merge(['event_id' => $eventId]);
        $request->validate(['event_id' => 'required|ulid|exists:events,id']);

        $event = $request->user()->events()->findOrFail($eventId);

        $attendees = $event->attendees()
            ->with(['orderItem.ticket:id,name'])
            ->whereHas('orderItem.order', fn($q) => $q->whereIn('payment_status', ['paid', 'complimentary']))
            ->latest()
            ->cursorPaginate(20);

        return response()->json(
            AttendeeResource::collection($attendees)->response()->getData(true)
        );
    }
}
