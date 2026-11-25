<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class WithdrawalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyCode = $request->attributes->get('currency_code');
        $locale       = $request->attributes->get('locale', 'en');

        return [
            'amount_raw'   => (int) $this->amount,
            'amount'       => Number::currency((int) $this->amount, in: $currencyCode, locale: $locale, precision: 0),
            'status'       => $this->status,
            'note'         => $this->note,
            'admin_note'   => $this->admin_note,
            'settled_at'   => $this->settled_at,
            'requested_at' => $this->created_at,
        ];
    }
}