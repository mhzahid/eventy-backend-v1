<?php

namespace App\Jobs;

use App\Services\OrganizerStatsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class ReconcileOrganizerStats implements ShouldQueue
{
    use Queueable;

    public function handle(OrganizerStatsService $statsService): void
    {
        $now = now();

        // Fetch all organizer IDs that have paid orders in the current day, month, or year.
        // Reconcile only live periods — past periods are immutable once closed.
        $organizerIds = DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->where('orders.payment_status', 'paid')
            ->whereYear('orders.created_at', $now->year)
            ->distinct()
            ->pluck('events.user_id');

        foreach ($organizerIds as $organizerId) {
            $statsService->recompute($organizerId, $now);
        }
    }
}
