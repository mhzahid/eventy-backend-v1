<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            // 'slug'                   => $this->slug,
            // 'tag_line'               => $this->tag_line,
            'description'            => $this->description,
            'location'               => $this->location,
            'map_url'                => $this->map_url,
            'venue_map'              => $this->venue_map_image,
            // 'is_physical'            => $this->is_physical,
            // 'status'                 => $this->status,
            'event_date'             => $this->event_date,
            'start_time'             => $this->start_time,
            'sell_end_date'          => $this->sell_end_date?->toDateString(),
            'sell_end_time'          => $this->sell_end_time,
            'is_age_restricted'      => $this->is_age_restricted,
            'charge_transaction_fee' => $this->charge_transaction_fee,
            'max_tickets_per_order'  => $this->max_tickets_per_order,
            'brand_color'            => $this->brand_color,
            'event_banner'           => $this->event_banner,
            'org_name'               => $this->organizer->organizerProfile?->organization_name,
            'support_email'          => $this->support_email,
            'support_number'         => $this->support_number,
            'facebook_url'           => $this->facebook_url,
            'terms_and_conditions'   => $this->terms_and_conditions,
            // 'is_featured'            => $this->is_featured,
            'category'               => $this->category?->name,
            'payment_gateway'        => $this->paymentGateway?->name,
            'timezone'               => $this->organizer->organizerProfile?->country?->timezone ?? 'UTC',
            'currency_code'          => $request->attributes->get('currency_code'),
            'sponsors'               => $this->sponsors,
            'highlights'             => $this->highlights,
            'participants'           => ParticipantResource::collection($this->whenLoaded('participants')),
            'form'                   => $this->whenLoaded('form', fn() => $this->form?->fields),
        ];
    }
}
