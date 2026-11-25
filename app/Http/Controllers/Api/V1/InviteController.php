<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Event\StoreInviteRequest;
use App\Jobs\GenerateTicketImage;
use App\Jobs\SendGuestTicketsJob;
use App\Models\Ticket;
use App\Services\InviteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class InviteController extends Controller
{
    public function __construct(private readonly InviteService $inviteService) {}

    public function store(StoreInviteRequest $request, string $eventId): JsonResponse
    {
        $request->merge(['event_id' => $eventId]);
        $request->validate(['event_id' => 'required|ulid|exists:events,id']);

        $user   = $request->user();
        $event  = $user->events()->findOrFail($eventId);
        $data   = $request->validated();
        $ticket = Ticket::findOrFail($data['ticket_id']);

        $attendees = $this->inviteService->create($user, $event->id, $data);

        $sendJob = new SendGuestTicketsJob(
            email: $data['email'],
            event: $event,
            ticketName: $ticket->name,
            attendeeIds: array_map(fn($a) => $a->id, $attendees),
        );

        if (Storage::disk('public')->exists('events/tickets/' . $event->id . '.jpeg')) {
            dispatch($sendJob);
        } else {
            Bus::chain([new GenerateTicketImage($event), $sendJob])->dispatch();
        }

        return response()->json([
            'message' => 'Guest ticket sent successfully.',
        ], 201);
    }
}