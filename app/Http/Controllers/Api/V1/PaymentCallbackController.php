<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderConfirmationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentCallbackController extends Controller
{
    public function __construct(private OrderConfirmationService $confirmation) {}

    public function handle(Request $request, string $gateway, string $status)
    {
        if ($status === 'cancel') {
            return $this->releaseAndRedirect($request, $gateway, 'cancelled', 'cancelled');
        }

        if ($status === 'fail') {
            return $this->releaseAndRedirect($request, $gateway, 'failed', 'failed');
        }

        $gw      = PaymentService::for($gateway);
        $payment = $gw->verify($request->all());


        if (!$payment->success) {
            return $this->releaseAndRedirect($request, $gateway, 'failed', 'failed');
        }

        $orderId = $gw->getOrderId($request->all());

        if (!$orderId) {
            return response()->json(['message' => 'Order ID not found.'], 400);
        }

        if ($payment->transactionId) {
            Order::where('id', $orderId)
                ->whereNull('gateway_transaction_id')
                ->update(['gateway_transaction_id' => $payment->transactionId]);
        }

        $result = $this->confirmation->confirm($orderId);

        if (!$result) {
            return $this->releaseAndRedirect($request, $gateway, 'failed', 'failed');
        }

        $slug = Order::with('event:id,slug')->find($orderId)?->event?->slug;

        $redirectUrl = 'https://' . $slug . '.eventy.com/orders/' . $orderId . '?status=success';

        return redirect($redirectUrl);
    }


    private function releaseAndRedirect(Request $request, string $gateway, string $orderStatus, string $paymentStatus)
    {
        $orderId = PaymentService::for($gateway)->getOrderId($request->all());

        if ($orderId) {
            DB::transaction(function () use ($orderId, $orderStatus, $paymentStatus) {
                $order = Order::lockForUpdate()->find($orderId);

                if ($order && $order->payment_status === 'unpaid') {
                    $item   = $order->items()->first();
                    $ticket = $item?->ticket()->lockForUpdate()->first();
                    $ticket?->decrement('reserved_quantity', $item->quantity);
                    $order->update(['status' => $orderStatus, 'payment_status' => $paymentStatus]);
                }
            });

            $slug = Order::with('event:id,slug')->find($orderId)?->event?->slug;

            $redirectUrl = 'https://' . $slug . '.eventy.com/orders/' . $orderId . '?status=' . $paymentStatus;
            return redirect($redirectUrl);
        }

        return response()->json(['message' => 'Order ID not found.'], 400);
    }
}
