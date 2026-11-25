<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class PublicTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyCode = $request->attributes->get('currency_code');
        $locale       = $request->attributes->get('locale', 'en');
        $price        = (int) ($this->price ?? 0);
        $remain       = max(0, $this->quantity - $this->sold_quantity);

        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'price_raw'   => $price,
            'price'       => Number::currency($price, in: $currencyCode, locale: $locale, precision: 0),
            // 'quantity'    => $this->quantity,
            'remain'      => $remain,
            'is_sold_out' => $remain === 0,
            'includes'    => $this->includes,
        ];
    }
}
