<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Jobs\GeneratePaidTicketsJob;
use App\Jobs\GenerateTicketImage;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

class GenerateEventTickets implements ShouldQueue
{
    public bool $afterCommit = true;

    public function handle(OrderPaid $event): void
    {
        $order = Order::with('event')->find($event->orderId);

        if (!$order) {
            return;
        }

        $generateJob = new GeneratePaidTicketsJob($order);

        if (Storage::disk('public')->exists('events/tickets/' . $order->event->id . '.jpeg')) {
            dispatch($generateJob);
        } else {
            Bus::chain([new GenerateTicketImage($order->event), $generateJob])->dispatch();
        }
    }
}
