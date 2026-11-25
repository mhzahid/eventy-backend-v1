<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class EventListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyCode = $request->attributes->get('currency_code');
        $locale       = $request->attributes->get('locale', 'en');

        return [
            'id'             => $this->id,
            'name'           => $this->name,
            'status'         => $this->status,
            'event_date'     => $this->event_date,
            'location'       => $this->location,
            'category'       => $this->category?->name,
            'total_quantity' => (int) ($this->total_quantity ?? 0),
            'total_sold'     => (int) ($this->total_sold ?? 0),
            'revenue_raw'    => (int) ($this->total_revenue ?? 0),
            'revenue'        => Number::currency((int) ($this->total_revenue ?? 0), in: $currencyCode, locale: $locale, precision: 0),

        ];
    }
}
