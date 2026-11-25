<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Attendee;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Ticket;
use App\Models\User;
use App\Services\OrderConfirmationService;
use App\Services\PaymentService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(private OrderConfirmationService $confirmation) {}

    public function store(Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:26',
            'email'         => 'required|email',
            'phone'         => 'required|string|max:25',
            'ticket_id'     => 'required|ulid|exists:tickets,id',
            'quantity'      => 'required|integer|min:1|max:10',
            'date_of_birth' => 'nullable|date',
            'form_data'     => 'nullable|array',
        ]);

        $ticket = Ticket::with([
            'event.paymentGateway',
            'event.organizer.organizerProfile.country',
        ])->findOrFail($request->ticket_id);

        $event = $ticket->event;

        if ($event->status !== EventStatus::Published || $event->is_blocked || $event->is_on_hold) {
            return response()->json(['message' => 'This event is not available for ticket purchases.'], 422);
        }

        // if ($ticket->event->start_time) {
        //     $saleStart = Carbon::parse($sellEndDate . ' ' . $ticket->event->getRawOriginal('start_time'));
        //     if (now()->lt($saleStart)) {
        //         return response()->json(['message' => 'Ticket sales have not started yet.'], 422);
        //     }
        // }

        if ($event->sell_end_time) {
            $country      = $event->organizer?->organizerProfile?->country;
            $timezone     = $country?->timezone ?? 'UTC';
            $sellEndDate  = $event->sell_end_date->toDateString();

            $saleEnd = Carbon::parse($sellEndDate . ' ' . $event->sell_end_time, $timezone);
            if (now()->gt($saleEnd)) {
                return response()->json(['message' => 'Ticket sales have ended.'], 422);
            }
        }

        // Fast pre-check (no lock) — avoids transaction overhead for obviously sold-out tickets
        $available = $ticket->quantity - $ticket->sold_quantity - $ticket->reserved_quantity;
        if ($request->quantity > $available) {
            return response()->json(['message' => 'Not enough tickets available.'], 422);
        }

        // Resolve user and prefix before the transaction so the lock is held as briefly as possible
        $user = User::firstOrCreate(
            ['email' => $request->email],
            ['name' => $request->name, 'phone' => $request->phone]
        );

        if ($user->wasRecentlyCreated) {
            $user->assignRole('user');
        }

        $prefix = strtoupper($event->prefix);

        $metaData = json_encode([
            'date_of_birth' => $request->date_of_birth,
            'form_data'     => $request->form_data,
        ]);

        // Resolve payment gateway and create order
        $gateway   = $event->paymentGateway;

        // ── Phase 1: Reserve (lock is held for milliseconds) ──────────────────
        $order = DB::transaction(function () use ($request, $ticket, $event, $user, $prefix, $metaData, $gateway) {
            $ticket = Ticket::lockForUpdate()->findOrFail($ticket->id);

            $available = $ticket->quantity - $ticket->sold_quantity - $ticket->reserved_quantity;
            if ($request->quantity > $available) {
                throw ValidationException::withMessages(['quantity' => 'Not enough tickets available.']);
            }

            $ticket->increment('reserved_quantity', $request->quantity);

            $subtotal = $ticket->price * $request->quantity;
            $now      = now();

            // Calculate and Apply transaction fee
            $feeAmount = 0;
            if ($subtotal > 0 && $event->charge_transaction_fee) {
                $feeAmount = $gateway->type === 'percentage'
                    ? round($subtotal * $gateway->transaction_fee / 100, 2)
                    : (float) $gateway->transaction_fee;
            }

            $order = Order::create([
                'user_id'         => $user->id,
                'event_id'        => $ticket->event_id,
                'subtotal_amount' => $subtotal,
                'discount_amount' => 0,
                'fee_amount'      => $feeAmount,
                'total_amount'    => $subtotal + $feeAmount,
                'payment_method'  => $subtotal > 0 ? $gateway->name : 'free',
                'reserved_at'     => $now,
                'expires_at'      => $now->copy()->addMinutes(10),
            ]);

            $item = OrderItem::create([
                'order_id'   => $order->id,
                'ticket_id'  => $ticket->id,
                'quantity'   => $request->quantity,
                'unit_price' => $ticket->price,
                'subtotal'   => $subtotal,
            ]);

            $attendees = [];
            for ($i = 0; $i < $request->quantity; $i++) {
                $tc = Str::take(Str::ulid(), -4) . bin2hex(random_bytes(2));

                $attendees[] = [
                    'id'            => Str::lower((string) Str::ulid()),
                    'order_item_id' => $item->id,
                    'event_id'      => $ticket->event_id,
                    'name'          => $request->name,
                    'email'         => $request->email,
                    'phone'         => $request->phone,
                    'ticket_code'   => $prefix . '-' . $tc,
                    'meta_data'     => $metaData,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
            Attendee::insert($attendees);

            return $order;
        });

        // ── Phase 2: Free ticket — confirm immediately, skip payment gateway ─
        if ($order->total_amount == 0) {
            $confirmed = $this->confirmation->confirm($order->id);

            if (!$confirmed) {
                return response()->json(['message' => 'Order confirmation failed.'], 422);
            }

            $redirectUrl = 'https://' . $ticket->event->slug . '.eventy.com/orders/' . $order->id . '?status=success';

            // return redirect($redirectUrl);
            return response()->json([
                'message'      => 'Order confirmed.',
                'redirect_url' => $redirectUrl,
                'order_id'     => $order->id,
                'free'         => true,
            ]);
        }

        // ── Phase 3: Initiate payment (no DB lock held) ───────────────────────
        $payment = PaymentService::for($gateway->name)->initiate([
            'order_id' => $order->id,
            'amount'   => $order->total_amount,
            'currency' => 'BDT',
            'customer' => [
                'name'  => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
            ],
            'success_url' => route('payment.callback', ['gateway' => $gateway->name, 'status' => 'success']),
            'fail_url'    => route('payment.callback', ['gateway' => $gateway->name, 'status' => 'fail']),
            'cancel_url'  => route('payment.callback', ['gateway' => $gateway->name, 'status' => 'cancel']),
        ]);

        if (!$payment->success) {
            DB::transaction(function () use ($order, $ticket, $request) {
                $ticket->decrement('reserved_quantity', $request->quantity);
                $order->update(['status' => 'cancelled', 'payment_status' => 'failed']);
            });

            return response()->json(['message' => $payment->message], 422);
        }

        // Store the gateway's payment reference so it can be queried later
        if ($payment->transactionId) {
            $order->update(['gateway_payment_id' => $payment->transactionId]);
        }

        return response()->json([
            'message'      => 'Redirect to payment gateway.',
            'redirect_url' => $payment->redirectUrl,
            'order_id'     => $order->id,
        ]);
    }

    // public function checkPayment(Request $request, string $orderId): JsonResponse
    // {
    //     $order = Order::with(['event:id,slug,payment_gateway_id', 'event.paymentGateway:id,name'])
    //         ->find($orderId);

    //     if (!$order) {
    //         return response()->json(['message' => 'Order not found.'], 404);
    //     }

    //     if ($order->payment_status === 'paid') {
    //         return response()->json(['status' => $order->payment_status]);
    //     }

    //     if (!$order->gateway_payment_id) {
    //         return response()->json(['status' => $order->payment_status]);
    //     }

    //     $gatewayName = $order->event->paymentGateway->name;
    //     $result      = PaymentService::for($gatewayName)->queryPayment($order->gateway_payment_id);

    //     if (!$result->success) {
    //         return response()->json(['status' => $order->payment_status]);
    //     }

    //     $confirmed = $this->confirmation->confirm($orderId);

    //     if (!$confirmed) {
    //         return response()->json(['status' => 'failed'], 422);
    //     }

    //     $slug = $order->event?->slug;

    //     return response()->json([
    //         'status'       => 'paid',
    //         'redirect_url' => 'https://' . $slug . '.eventy.com/orders/' . $orderId . '?status=success',
    //     ]);
    // }
}