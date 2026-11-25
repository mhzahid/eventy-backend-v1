<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function request(User $organizer, array $data): void
    {
        DB::transaction(function () use ($organizer, $data) {
            $balance = $organizer->organizerBalance()->lockForUpdate()->first();

            if (!$balance) {
                throw ValidationException::withMessages([
                    'withdrawal_amount' => 'No balance record found. You have no available funds.',
                ]);
            }

            $pending = $organizer->withdrawals()
                ->whereIn('status', ['pending', 'processing'])
                ->sum('amount');

            $netAvailable = $balance->available_amount - $pending;

            if ($data['withdrawal_amount'] > $netAvailable) {
                throw ValidationException::withMessages([
                    'withdrawal_amount' => "Insufficient balance. Net available: {$netAvailable}",
                ]);
            }

            $organizer->withdrawals()->create([
                'amount' => $data['withdrawal_amount'],
                'note'   => $data['note'] ?? null,
            ]);
        });
    }
}