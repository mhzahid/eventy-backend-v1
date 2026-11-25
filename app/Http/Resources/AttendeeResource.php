<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            // 'ticket_code'     => $this->ticket_code,
            'ticket_name'     => $this->orderItem->ticket->name,
            'is_entered'    => $this->entry_status,
            'entry_time'      => $this->entry_time?->toDateTimeString(),
        ];
    }
}