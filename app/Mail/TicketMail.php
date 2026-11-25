<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User   $buyer,
        public Event  $event,
        public array  $pdfAttachments, // [['content' => string, 'filename' => string]]
    ) {}

    public function envelope(): Envelope
    {
        $count = count($this->pdfAttachments);
        $subject = $count === 1
            ? 'Your Ticket - ' . $this->event->name
            : 'Your Tickets - ' . $this->event->name;

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.ticket');
    }

    public function attachments(): array
    {
        return collect($this->pdfAttachments)
            ->map(fn($pdf) => Attachment::fromData(fn() => $pdf['content'], $pdf['filename'])
                ->withMime('application/pdf'))
            ->all();
    }
}
