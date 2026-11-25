<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyCode = $request->attributes->get('currency_code');
        $locale       = $request->attributes->get('locale', 'en');

        $price   = (int) ($this->price ?? 0);
        $revenue = (int) ($price * $this->sold_quantity);

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'price_raw'      => $price,
            'price'          => Number::currency($price, in: $currencyCode, locale: $locale, precision: 0),
            'quantity'       => $this->quantity,
            'sold_quantity'  => $this->sold_quantity,
            'remain'         => $this->quantity - $this->sold_quantity,
            'revenue_raw'    => $revenue,
            'revenue'        => Number::currency($revenue, in: $currencyCode, locale: $locale, precision: 0),
            'includes'       => $this->includes,
            'status'         => $this->status,
        ];
    }
}
