<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Number;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $currencyCode = $request->attributes->get('currency_code');
        $locale       = $request->attributes->get('locale', 'en');

        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            'full_slug'              => $this->slug . '.' . parse_url(config('app.url'), PHP_URL_HOST),
            // 'tag_line'               => $this->tag_line,
            'location'               => $this->location,
            'map_url'                => $this->map_url,
            'venue_map'              => $this->venue_map_image,
            'is_physical'            => $this->is_physical,
            'status'                 => $this->status,
            'event_date'             => $this->event_date,
            'start_time'             => $this->start_time,
            'sell_end_date'          => $this->sell_end_date,
            'sell_end_time'          => $this->sell_end_time,
            'is_age_restricted'      => $this->is_age_restricted,
            'charge_transaction_fee' => $this->charge_transaction_fee,
            'brand_color'            => $this->brand_color,
            'event_banner'           => $this->event_banner,
            'support_email'          => $this->support_email,
            'support_number'         => $this->support_number,
            'facebook_url'           => $this->facebook_url,
            'is_visible'             => $this->is_visible,
            'is_blocked'             => $this->is_blocked,
            'is_on_hold'             => $this->is_on_hold,
            'is_featured'            => $this->is_featured,
            'category'               => $this->category->name,
            'payment_gateway'        => $this->paymentGateway->name,
            'total_quantity'         => $this->whenLoaded('tickets', fn() => (int) $this->tickets->sum('quantity'), 0),
            'total_sold'             => $this->whenLoaded('tickets', fn() => (int) $this->tickets->sum('sold_quantity'), 0),
            'revenue_raw'            => (int) ($this->total_revenue ?? 0),
            'revenue'                => Number::currency((int) ($this->total_revenue ?? 0), in: $currencyCode, locale: $locale, precision: 0),
            'tickets'                => TicketResource::collection($this->whenLoaded('tickets')),
            'participants'           => ParticipantResource::collection($this->whenLoaded('participants')),
            'created_at'             => $this->created_at,
        ];
    }
}
