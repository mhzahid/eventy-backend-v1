<?php

namespace App\Mail;

use App\Models\Event;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GuestTicketMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<\App\Models\Attendee> $attendees
     */
    public function __construct(
        public Event  $event,
        public string $ticketName,
        public array  $attendees,
        public array  $pdfAttachments, // [['content' => string, 'filename' => string]]
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event Invitation - ' . $this->event->name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.guest-ticket',
        );
    }

    public function attachments(): array
    {
        return collect($this->pdfAttachments)
            ->map(fn($pdf) => Attachment::fromData(fn() => $pdf['content'], $pdf['filename'])
                ->withMime('application/pdf'))
            ->all();
    }
}
