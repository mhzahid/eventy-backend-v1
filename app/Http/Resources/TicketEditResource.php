<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketEditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'price'    => (float) ($this->price ?? 0),
            'quantity' => (int) $this->quantity,
            'includes' => $this->includes,
        ];
    }
}
