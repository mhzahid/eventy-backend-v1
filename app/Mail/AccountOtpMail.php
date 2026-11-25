<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public int $otp, public string $purpose, public int $expiresAt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Verify Your ' . ucfirst($this->purpose) . ' Change',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.account_otp',
        );
    }
}
