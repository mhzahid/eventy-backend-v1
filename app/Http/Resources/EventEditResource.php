<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventEditResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        $defaultTime = '23:59:00';

        $sellEndDate = $this->sell_end_date?->toDateString() == $this->event_date?->toDateString() && $this->sell_end_time == $defaultTime ? null : $this->sell_end_date;

        $sellEndTime = $this->sell_end_time == $defaultTime ? null : $this->sell_end_time;

        return [
            'id'                     => $this->id,
            'name'                   => $this->name,
            'slug'                   => $this->slug,
            // 'tag_line'               => $this->tag_line,
            'location'               => $this->location,
            'map_url'                => $this->map_url,
            'venue_map'              => $this->venue_map_image,
            'is_physical'            => $this->is_physical,
            'status'                 => $this->status,
            'event_date'             => $this->event_date,
            'start_time'             => $this->start_time,
            'sell_end_date'          => $sellEndDate,
            'sell_end_time'          => $sellEndTime,
            'is_age_restricted'      => $this->is_age_restricted,
            'charge_transaction_fee' => $this->charge_transaction_fee,
            'brand_color'            => $this->brand_color,
            'event_banner'           => $this->event_banner,
            'description'            => $this->description,
            'support_email'          => $this->support_email,
            'support_number'         => $this->support_number,
            'facebook_url'           => $this->facebook_url,
            // 'is_visible'             => $this->is_visible,
            // 'is_blocked'             => $this->is_blocked,
            // 'is_on_hold'             => $this->is_on_hold,
            // 'is_featured'            => $this->is_featured,
            'max_tickets_per_order'  => $this->max_tickets_per_order,
            'category'               => $this->category,
            'payment_gateway'        => $this->paymentGateway,
            'highlights'             => $this->highlights,
            'terms_and_conditions'   => $this->terms_and_conditions,
            'sponsors'               => $this->sponsors,
            'form'                   => EventFormResource::make($this->whenLoaded('form')),
            'tickets'                => TicketEditResource::collection($this->whenLoaded('tickets')),
            'participants'           => ParticipantResource::collection($this->whenLoaded('participants')),
        ];
    }
}
