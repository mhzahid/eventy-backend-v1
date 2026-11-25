<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\TicketGenerationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeneratePaidTicketsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries   = 3;
    public int $backoff = 60;

    public function __construct(public Order $order) {}

    public function handle(TicketGenerationService $ticketService): void
    {
        $ticketService->generateForOrder($this->order);
    }

    public function failed(Throwable $e): void
    {
        Log::error('GeneratePaidTicketsJob failed.', [
            'order_id' => $this->order->id,
            'error'    => $e->getMessage(),
        ]);
    }
}
