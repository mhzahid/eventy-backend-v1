<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerResource extends JsonResource
{
    private string $token;
    private mixed $tokenExpiresAt = null;

    public function withToken(string $token, mixed $expiresAt = null): static
    {
        $this->token          = $token;
        $this->tokenExpiresAt = $expiresAt;
        return $this;
    }

    public function toArray(Request $request): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => [
                'organization_name' => $this->organizerProfile?->organization_name,
                'country'           => $this->organizerProfile?->country->name,
                'currency'           => $this->organizerProfile?->country->currency_code,
                'timezone'           => $this->organizerProfile?->country->timezone,
                'dial_code'          => $this->organizerProfile?->country->dial_code,
            ],
            'token'            => $this->token ?? null,
            'token_expires_at' => $this->tokenExpiresAt,
        ];
    }
}
