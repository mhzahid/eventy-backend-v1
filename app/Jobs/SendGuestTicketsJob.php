<?php

namespace App\Jobs;

use App\Mail\GuestTicketMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Services\TicketGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendGuestTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(
        public string $email,
        public Event  $event,
        public string $ticketName,
        public array  $attendeeIds,
    ) {}

    public function handle(TicketGenerationService $ticketService): void
    {
        $attendees = Attendee::findMany($this->attendeeIds)->all();

        if (empty($attendees)) {
            Log::warning('SendGuestTicketsJob: no attendees found, skipping.', [
                'attendee_ids' => $this->attendeeIds,
            ]);
            return;
        }

        $pdfAttachments = $ticketService->generateGuestPdfs($attendees, $this->ticketName, $this->event);

        Mail::to($this->email)->send(new GuestTicketMail(
            event: $this->event,
            ticketName: $this->ticketName,
            attendees: $attendees,
            pdfAttachments: $pdfAttachments,
        ));
    }

    public function failed(Throwable $e): void
    {
        Log::error('SendGuestTicketsJob failed.', [
            'email'       => $this->email,
            'event_id'    => $this->event->id,
            'ticket_name' => $this->ticketName,
            'error'       => $e->getMessage(),
        ]);
    }
}
