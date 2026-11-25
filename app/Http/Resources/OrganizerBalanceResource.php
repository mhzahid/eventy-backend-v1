<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class OrganizerBalanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {

        $currencyCode = $this->resource[1];
        $locale       = $this->resource[2] ?? 'en';

        return [
            'total_sales_raw'      => (int) ($this->resource[0]?->total_sales ?? 0), 
            'total_sales'      => Number::currency((int) ($this->resource[0]?->total_sales ?? 0), in: $currencyCode, locale: $locale, precision: 0),

            'available_amount_raw' => (int) ($this->resource[0]?->available_amount ?? 0),
            'available_amount' => Number::currency((int) ($this->resource[0]?->available_amount ?? 0), in: $currencyCode, locale: $locale, precision: 0),

            'withdrawn_amount_raw' => (int) ($this->resource[0]?->withdrawn_amount ?? 0),
            'withdrawn_amount' => Number::currency((int) ($this->resource[0]?->withdrawn_amount ?? 0), in: $currencyCode, locale: $locale, precision: 0),
        ];
    }
}