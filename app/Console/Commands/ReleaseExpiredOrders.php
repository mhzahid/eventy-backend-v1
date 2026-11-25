<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReleaseExpiredOrders extends Command
{
    protected $signature   = 'orders:release-expired';
    protected $description = 'Release reserved tickets for orders that expired without payment';

    public function handle(): void
    {
        $expired = Order::with('items.ticket')
            ->where('payment_status', 'unpaid')
            ->where('expires_at', '<', now())
            ->get();

        if ($expired->isEmpty()) {
            return;
        }

        foreach ($expired as $order) {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    $item->ticket()->lockForUpdate()->first()
                        ?->decrement('reserved_quantity', $item->quantity);
                }

                $order->update(['status' => 'cancelled', 'payment_status' => 'expired']);
            });
        }

        $this->info("Released {$expired->count()} expired order(s).");
    }
}