<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Withdrawal\StoreWithdrawalRequest;
use App\Http\Resources\OrganizerBalanceResource;
use App\Http\Resources\WithdrawalResource;
use App\Models\OrganizerBalance;
use App\Services\WithdrawalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class OrganizerBalanceController extends Controller
{
    public function __construct(private readonly WithdrawalService $withdrawalService) {}
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->loadMissing('organizerProfile.country');
        $country      = $user->organizerProfile?->country;
        $currencyCode = $country?->currency_code;
        $locale       = $country?->locale ?? 'en';

        $pendingWithdrawal = $user->withdrawals()
            ->whereIn('status', ['pending', 'processing'])
            ->sum('amount');

        $balance = $user->organizerBalance ?? new OrganizerBalance();

        return response()->json([
            'data' => [
                ...(new OrganizerBalanceResource([$balance, $currencyCode, $locale]))->toArray($request),
                'pending_withdrawal' => Number::currency((int) $pendingWithdrawal, in: $currencyCode, locale: $locale, precision: 0),
                'pending_withdrawal_raw' => (int) $pendingWithdrawal,
            ],
        ]);
    }

    public function withdraw(StoreWithdrawalRequest $request): JsonResponse
    {
        $user = $request->user();

        $this->withdrawalService->request($user, $request->validated());

        Cache::tags("organizer:{$user->id}:transactions")->flush();

        return response()->json(['message' => 'Withdrawal request submitted successfully.'], 201);
    }

    public function transactions(Request $request): JsonResponse
    {
        $user     = $request->user();
        $cursor   = $request->query('cursor', '');
        $cacheKey = "organizer:{$user->id}:transactions:cursor:{$cursor}";

        $user->loadMissing('organizerProfile.country');
        $country = $user->organizerProfile?->country;
        $request->attributes->set('currency_code', $country?->currency_code);
        $request->attributes->set('locale', $country?->locale ?? 'en');

        $data = Cache::tags("organizer:{$user->id}:transactions")->remember($cacheKey, 300, function () use ($user, $request) {
            $withdrawals = $user->withdrawals()->latest()->cursorPaginate(20);

            if ($withdrawals->isEmpty()) {
                return ['message' => 'No transactions found.'];
            }

            $paginated                  = WithdrawalResource::collection($withdrawals)->response()->getData(true);
            $paginated['currency_code'] = $request->attributes->get('currency_code');

            return $paginated;
        });

        return response()->json($data);
    }
}