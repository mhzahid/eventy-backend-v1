<?php

namespace App\Console\Commands;

use App\Jobs\GenerateTicketImage;
use App\Models\Event;
use Illuminate\Console\Command;

class GenerateEventTicketImage extends Command
{
    protected $signature   = 'event:generate-ticket-image {event_id : The ULID of the event}';
    protected $description = 'Generate the ticket background image for an event';

    public function handle(): int
    {
        $event = Event::find($this->argument('event_id'));

        if (!$event) {
            $this->error('Event not found.');
            return self::FAILURE;
        }

        (new GenerateTicketImage($event))->handle();

        $this->info("Ticket image generated for event: {$event->name}");

        return self::SUCCESS;
    }
}
