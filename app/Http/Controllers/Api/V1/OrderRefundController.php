<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\RefundOrderRequest;
use App\Services\OrderRefundService;
use Illuminate\Http\JsonResponse;

class OrderRefundController extends Controller
{
    public function __construct(private OrderRefundService $refundService) {}

    public function refund(RefundOrderRequest $request): JsonResponse
    {
        $result = $this->refundService->initiate($request->organizer_id, $request->order_id);

        if (!$result['success']) {
            return response()->json(
                ['message' => $result['message']],
                $result['status']
            );
        }

        if (isset($result['refund_transaction_id'])) {
            return response()->json([
                'message' => $result['message'],
                'refund_transaction_id' => $result['refund_transaction_id'],
                'transaction_id' => $result['transaction_id']
            ]);
        }

        return response()->json([
            'message'        => 'Refund successful.',
            'transaction_id' => $result['transaction_id'],
        ]);
    }
}