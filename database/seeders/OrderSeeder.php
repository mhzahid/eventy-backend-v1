<?php

namespace Database\Seeders;

use App\Models\Attendee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrganizerBalance;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $buyer = User::where('email', 'user@eventy.test')->first();

        Ticket::with('event')->take(2)->get()->each(function (Ticket $ticket) use ($buyer) {
            $quantity = 2;
            $subtotal = $ticket->price * $quantity;

            DB::transaction(function () use ($ticket, $buyer, $quantity, $subtotal) {
                $order = Order::create([
                    'user_id'         => $buyer->id,
                    'event_id'        => $ticket->event_id,
                    'subtotal_amount' => $subtotal,
                    'discount_amount' => 0,
                    'total_amount'    => $subtotal,
                    'status'          => 'confirmed',
                    'payment_status'  => 'paid',
                    'payment_method'  => $ticket->event->payment_gateway,
                    'currency'        => 'BDT',
                    'reserved_at'     => now(),
                    'expires_at'      => now()->addMinutes(15),
                ]);

                $item = OrderItem::create([
                    'order_id'   => $order->id,
                    'ticket_id'  => $ticket->id,
                    'quantity'   => $quantity,
                    'unit_price' => $ticket->price,
                    'subtotal'   => $subtotal,
                ]);

                for ($i = 0; $i < $quantity; $i++) {
                    Attendee::create([
                        'order_item_id' => $item->id,
                        'event_id'      => $ticket->event_id,
                        'name'          => $buyer->name,
                        'email'         => $buyer->email,
                        'phone'         => $buyer->phone,
                        'ticket_code'   => strtoupper($ticket->event->prefix) . '-' . strtoupper(Str::random(8)),
                    ]);
                }

                $ticket->increment('sold_quantity', $quantity);

                $organizerUserId = $ticket->event->user_id;

                OrganizerBalance::firstOrCreate(
                    ['user_id' => $organizerUserId],
                    ['total_sales' => 0, 'available_amount' => 0, 'withdrawn_amount' => 0]
                );

                OrganizerBalance::where('user_id', $organizerUserId)->update([
                    'total_sales'      => DB::raw("total_sales + {$subtotal}"),
                    'available_amount' => DB::raw("available_amount + {$subtotal}"),
                ]);
            });
        });
    }
}
