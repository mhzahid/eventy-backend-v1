<?php

namespace App\Http\Controllers\Api\V1;

use App\Contracts\SnsCapable;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderConfirmationService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IpnController extends Controller
{
    public function __construct(private OrderConfirmationService $confirmation) {}

    public function handle(Request $request, string $gateway)
    {
        $gatewayService = PaymentService::for($gateway);

        if ($gateway === 'bkash') {
            $payload = json_decode($request->getContent(), true);
            Log::channel('bkashIPN')->info('SNS webhook payload', ['payload' => $payload]);
        } else {
            $payload = $request->all();
        }

        // AWS SNS subscription confirmation — one-time handshake when webhook is first registered
        if (
            $gatewayService instanceof SnsCapable &&
            $request->header('x-amz-sns-message-type') === 'SubscriptionConfirmation' &&
            ($payload['Type'] ?? '') === 'SubscriptionConfirmation'
        ) {
            $gatewayService->confirmSnsSubscription($payload);
        }

        $payment = $gatewayService->verify($payload);

        if (!$payment->success) {
            return response()->json(['message' => 'Invalid IPN.'], 422);
        }

        $orderId = $gatewayService->getOrderId($payload);

        if (!$orderId) {
            return response()->json(['message' => 'Missing order ID.'], 422);
        }

        if ($payment->transactionId) {
            Order::where('id', $orderId)
                ->whereNull('gateway_transaction_id')
                ->update(['gateway_transaction_id' => $payment->transactionId]);
        }

        $this->confirmation->confirm($orderId);

        return response()->json(['message' => 'IPN processed.']);
    }
}
