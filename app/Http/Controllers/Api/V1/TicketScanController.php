<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\TicketScanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TicketScanController extends Controller
{
    public function __construct(private readonly TicketScanService $scanService) {}

    public function eventListForScanner(Request $request): JsonResponse
    {
        $organizerId = $this->resolveOrganizerId($request);

        $events = Event::where('user_id', $organizerId)
            ->select('id', 'name', 'event_date', 'location')
            ->where('status', 'published')
            ->where('is_physical', true)
            ->whereDate('event_date', '>=', today())
            ->get();

        return response()->json(['data' => $events]);
    }

    public function scanTicket(Request $request): JsonResponse
    {
        $request->validate([
            'qr_data' => 'required|string',
            'event_id' => 'required'
        ]);

        $organizerId = $this->resolveOrganizerId($request);

        $event = Event::select('id')
            ->where('id', $request->event_id)
            ->where('user_id', $organizerId)
            ->firstOrFail();

        $parsed = $this->scanService->decryptQrData($request->input('qr_data'));

        if (!$parsed) {
            return response()->json(['message' => 'Invalid.'], 422);
        }

        if ($parsed['event_id'] !== $event->id) {
            return response()->json(['message' => 'This ticket does not belong to this event.'], 422);
        }

        $attendee = $event->attendees()
            ->with('orderItem:id,order_id', 'orderItem.order:id,payment_status')
            ->whereHas('orderItem.order', fn($q) => $q->whereIn('payment_status', ['paid', 'complimentary', 'refunded']))
            ->where('ticket_code', $parsed['ticket_code'])
            ->first();

        abort_if(!$attendee, 404);

        if ($attendee->orderItem?->order?->payment_status === 'refunded') {
            return response()->json(['message' => 'This ticket has been refunded.'], 422);
        }

        $now = now();

        $updated = $event->attendees()
            ->where('ticket_code', $parsed['ticket_code'])
            ->where('entry_status', false)
            ->update(['entry_status' => true, 'entry_time' => $now]);

        if ($updated === 0) {
            return response()->json([
                'success'    => false,
                'message'    => 'Ticket already used.',
                'entry_time' => $attendee->entry_time,
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Success',
            'data'    => [
                'name'       => $attendee->name,
                'entry_time' => $now,
            ],
        ]);
    }

    private function resolveOrganizerId(Request $request): string
    {
        $user = $request->user();

        if ($user->hasRole('ticket-scanner')) {
            $organizerId = $user->scannerProfile()->value('organizer_id');

            abort_if(is_null($organizerId), 403, 'Scanner is not linked to any organizer.');

            return $organizerId;
        }

        return $user->id;
    }
}
