<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CountryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'short_name'    => $this->short_name,
            'dial_code'     => $this->dial_code,
            'currency_code' => $this->currency_code,
            'timezone'      => $this->timezone,
        ];
    }
}
