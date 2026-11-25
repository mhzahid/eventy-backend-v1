<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('stats:backfill')]
#[Description('Backfill organizer_stats_daily/monthly/yearly from historical paid orders')]
class BackfillOrganizerStats extends Command
{
    public function handle(): void
    {
        $this->info('Backfilling daily stats...');
        $this->backfillDaily();

        $this->info('Backfilling monthly stats...');
        $this->backfillMonthly();

        $this->info('Backfilling yearly stats...');
        $this->backfillYearly();

        $this->info('Done.');
    }

    private function backfillDaily(): void
    {
        DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('events.user_id as organizer_id, DATE(orders.created_at) as stat_date, SUM(order_items.quantity) as total_sold')
            ->groupBy('events.user_id', 'stat_date')
            ->orderBy('stat_date')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::statement('
                        INSERT INTO organizer_stats_daily (organizer_id, stat_date, total_sold, created_at, updated_at)
                        VALUES (?, ?, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
                    ', [$row->organizer_id, $row->stat_date, $row->total_sold, $row->total_sold]);
                }
            });
    }

    private function backfillMonthly(): void
    {
        DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('events.user_id as organizer_id, YEAR(orders.created_at) as year, MONTH(orders.created_at) as month, SUM(order_items.quantity) as total_sold')
            ->groupBy('events.user_id', 'year', 'month')
            ->orderBy('year')->orderBy('month')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::statement('
                        INSERT INTO organizer_stats_monthly (organizer_id, year, month, total_sold, created_at, updated_at)
                        VALUES (?, ?, ?, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
                    ', [$row->organizer_id, $row->year, $row->month, $row->total_sold, $row->total_sold]);
                }
            });
    }

    private function backfillYearly(): void
    {
        DB::table('orders')
            ->join('events', 'orders.event_id', '=', 'events.id')
            ->join('order_items', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.payment_status', 'paid')
            ->selectRaw('events.user_id as organizer_id, YEAR(orders.created_at) as year, SUM(order_items.quantity) as total_sold')
            ->groupBy('events.user_id', 'year')
            ->orderBy('year')
            ->chunk(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::statement('
                        INSERT INTO organizer_stats_yearly (organizer_id, year, total_sold, created_at, updated_at)
                        VALUES (?, ?, ?, NOW(), NOW())
                        ON DUPLICATE KEY UPDATE total_sold = ?, updated_at = NOW()
                    ', [$row->organizer_id, $row->year, $row->total_sold, $row->total_sold]);
                }
            });
    }
}
