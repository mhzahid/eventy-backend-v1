<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrganizerBalance;
use App\Models\User;
use App\Services\Gateways\BkashGateway;
use Illuminate\Support\Facades\DB;

class OrderRefundService
{
    public function initiate(string $organizerId, string $orderId): array
    {
        $organizer = User::role('organizer')->where('id', $organizerId)->first();

        if (!$organizer) {
            return ['success' => false, 'status' => 404, 'message' => 'Organizer not found.'];
        }

        $order = Order::with([
            'event:id,user_id,payment_gateway_id',
            'event.paymentGateway:id,name',
            'items:id,order_id,ticket_id,quantity',
            'items.ticket:id',
        ])->where(function ($q) use ($orderId) {
            $q->where('id', $orderId)
                ->orWhere('gateway_transaction_id', $orderId);
        })->first();

        if (!$order) {
            return ['success' => false, 'status' => 404, 'message' => 'Order not found.'];
        }

        if (!$order->event || $order->event->user_id !== $organizer->id) {
            return ['success' => false, 'status' => 422, 'message' => 'Order does not belong to this organizer.'];
        }

        if ($order->payment_status === 'refunded') {
            return [
                'success' => true,
                'message' => 'Order is already refunded.',
                'transaction_id' => $order->gateway_transaction_id,
                'refund_transaction_id' => $order->refund_transaction_id,
            ];
        }

        if ($order->payment_status !== 'paid') {
            return ['success' => false, 'status' => 422, 'message' => 'Order is not eligible for refund.'];
        }

        if (!$order->gateway_payment_id || !$order->gateway_transaction_id) {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Payment transaction details not available for refund.',
            ];
        }

        $gatewayName = $order->event->paymentGateway?->name;

        if (!$gatewayName) {
            return ['success' => false, 'status' => 422, 'message' => 'Payment gateway not configured for this event.'];
        }

        $result = match ($gatewayName) {
            'bkash' => (new BkashGateway())->refund(
                $order->gateway_payment_id,
                $order->gateway_transaction_id,
                (float) $order->total_amount,
            ),
            default => null,
        };

        if (!$result) {
            return ['success' => false, 'status' => 422, 'message' => 'Refund is not supported for this payment gateway.'];
        }

        if (!$result->success) {
            return ['success' => false, 'status' => 422, 'message' => $result->message];
        }

        $this->applyRefundState($order, $result->transactionId);

        return ['success' => true, 'transaction_id' => $result->transactionId];
    }

    private function applyRefundState(Order $order, string $refundTransactionId): void
    {
        DB::transaction(function () use ($order, $refundTransactionId) {
            $order->update([
                'status'                => 'refunded',
                'payment_status'        => 'refunded',
                'refund_transaction_id' => $refundTransactionId,
            ]);

            foreach ($order->items as $item) {
                $item->ticket()->lockForUpdate()->first()?->decrement('sold_quantity', $item->quantity);
            }

            $organizerUserId = $order->event->user_id;
            $amount          = $order->total_amount;

            OrganizerBalance::where('user_id', $organizerUserId)->update([
                'total_sales'      => DB::raw("total_sales - {$amount}"),
                'available_amount' => DB::raw("available_amount - {$amount}"),
            ]);
        });
    }
}
