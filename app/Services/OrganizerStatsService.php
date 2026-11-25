<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OrganizerStatsService
{
    public function increment(string $organizerId, Carbon $date, int $quantity): void
    {
        // Each statement is atomic — safe under concurrent payments for the same organizer.
        // ON DUPLICATE KEY UPDATE increments rather than overwrites, so no lost updates.

        DB::statement('
            INSERT INTO organizer_stats_daily (organizer_id, stat_date, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = total_sold + ?, updated_at = NOW()
        ', [$organizerId, $date->toDateString(), $quantity, $quantity]);

        DB::statement('
            INSERT INTO organizer_stats_monthly (organizer_id, year, month, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = total_sold + ?, updated_at = NOW()
        ', [$organizerId, $date->year, $date->month, $quantity, $quantity]);

        DB::statement('
            INSERT INTO organizer_stats_yearly (organizer_id, year, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = total_sold + ?, updated_at = NOW()
        ', [$organizerId, $date->year, $quantity, $quantity]);
    }

    public function recompute(string $organizerId, Carbon $date): void
    {
        // Used by the reconciliation job to overwrite (not increment) a specific period
        // from the source of truth — handles missed increments or refunds.

        $dailyTotal = $this->rawDailyTotal($organizerId, $date->toDateString());
        DB::statement('
            INSERT INTO organizer_stats_daily (organizer_id, stat_date, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
        ', [$organizerId, $date->toDateString(), $dailyTotal, $dailyTotal]);

        $monthlyTotal = $this->rawMonthlyTotal($organizerId, $date->year, $date->month);
        DB::statement('
            INSERT INTO organizer_stats_monthly (organizer_id, year, month, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
        ', [$organizerId, $date->year, $date->month, $monthlyTotal, $monthlyTotal]);

        $yearlyTotal = $this->rawYearlyTotal($organizerId, $date->year);
        DB::statement('
            INSERT INTO organizer_stats_yearly (organizer_id, year, total_sold, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
        ', [$organizerId, $date->year, $yearlyTotal, $yearlyTotal]);
    }

    private function rawDailyTotal(string $organizerId, string $date): int
    {
        return (int) DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->where('orders.payment_status', 'paid')
            ->where('events.user_id', $organizerId)
            ->whereDate('orders.created_at', $date)
            ->sum('order_items.quantity');
    }

    private function rawMonthlyTotal(string $organizerId, int $year, int $month): int
    {
        return (int) DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->where('orders.payment_status', 'paid')
            ->where('events.user_id', $organizerId)
            ->whereYear('orders.created_at', $year)
            ->whereMonth('orders.created_at', $month)
            ->sum('order_items.quantity');
    }

    private function rawYearlyTotal(string $organizerId, int $year): int
    {
        return (int) DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->where('orders.payment_status', 'paid')
            ->where('events.user_id', $organizerId)
            ->whereYear('orders.created_at', $year)
            ->sum('order_items.quantity');
    }
}
