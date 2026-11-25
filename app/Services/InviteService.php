<?php

namespace App\Services;

use App\Models\Attendee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InviteService
{
    public function create(User $organizer, string $eventId, array $data): array
    {
        $ticket = Ticket::with('event')->findOrFail($data['ticket_id']);

        if ($ticket->event_id !== $eventId) {
            abort(422, 'Ticket does not belong to this event.');
        }

        return DB::transaction(function () use ($organizer, $ticket, $eventId, $data) {
            $order = Order::create([
                'user_id'         => $organizer->id,
                'event_id'        => $eventId,
                'subtotal_amount' => 0,
                'discount_amount' => 0,
                'total_amount'    => 0,
                'status'          => 'paid',
                'payment_status'  => 'complimentary',
            ]);

            $item = OrderItem::create([
                'order_id'   => $order->id,
                'ticket_id'  => $ticket->id,
                'quantity'   => $data['quantity'],
                'unit_price' => 0,
                'subtotal'   => 0,
            ]);

            $prefix    = strtoupper($ticket->event->prefix);
            $attendees = [];

            for ($i = 0; $i < $data['quantity']; $i++) {
                $attendees[] = Attendee::create([
                    'order_item_id' => $item->id,
                    'event_id'      => $eventId,
                    'name'          => $data['name'],
                    'email'         => $data['email'],
                    'phone'         => $data['phone'],
                    'ticket_code'   => $prefix . '-' . strtoupper(Str::random(8)),
                ]);
            }

            return $attendees;
        });
    }
}
