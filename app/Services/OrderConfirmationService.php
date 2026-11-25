<?php

namespace App\Services;

use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\OrganizerBalance;
use Illuminate\Support\Facades\DB;

class OrderConfirmationService
{
    public function confirm(string $orderId): bool
    {
        $statsPayload = null;

        $result = DB::transaction(function () use ($orderId, &$statsPayload) {
            $order = Order::lockForUpdate()->find($orderId);

            if (!$order) {
                return false;
            }

            if ($order->payment_status === 'paid') {
                return true;
            }

            $item   = $order->items()->first();
            $ticket = $item->ticket()->lockForUpdate()->first();

            $ticket->decrement('reserved_quantity', $item->quantity);
            $ticket->increment('sold_quantity', $item->quantity);

            $order->update([
                'status'         => 'confirmed',
                'payment_status' => 'paid',
            ]);

            $subtotal        = $order->total_amount;
            $organizerUserId = $ticket->event->user_id;

            OrganizerBalance::where('user_id', $organizerUserId)->update([
                'total_sales'      => DB::raw("total_sales + {$subtotal}"),
                'available_amount' => DB::raw("available_amount + {$subtotal}"),
            ]);

            $statsPayload = [$organizerUserId, $order->created_at, $item->quantity];

            OrderPaid::dispatch($organizerUserId, $order->id, $order->created_at);

            return true;
        });

        if ($result && $statsPayload) {
            app(OrganizerStatsService::class)->increment(...$statsPayload);
        }

        return $result;
    }
}
