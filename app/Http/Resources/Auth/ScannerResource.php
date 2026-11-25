<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScannerResource extends JsonResource
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
        $organizer = $this->scannerProfile?->organizer;

        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'organizer_name' => $organizer ? $organizer->organizerProfile?->organization_name : null,
            'token'            => $this->token ?? null,
            'token_expires_at' => $this->tokenExpiresAt,
        ];
    }
}
