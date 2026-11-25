<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'roles' => $this->getRoleNames(),
        ];

        $organizer = $this->organizer_data;

        if ($organizer) {
            $data = array_merge($data, [
                'profile'    => [
                    'organization_name' => $organizer['profile']->organization_name,
                    'business_type'     => $organizer['profile']->business_type,
                    'address'           => $organizer['profile']->address,
                    'website'           => $organizer['profile']->website,
                    'logo'              => $organizer['profile']->logo,
                    'country'           => $organizer['profile']->country?->name,
                    'currency'          => $organizer['profile']->country?->currency_code,
                    'timezone'          => $organizer['profile']->country?->timezone,
                    'dial_code'         => $organizer['profile']->country?->dial_code,
                ],
                'owner_info' => [
                    'name'         => $organizer['owner_info']?->name,
                    'id_type'      => $organizer['owner_info']?->id_type,
                    'id_image'     => $organizer['owner_info']?->id_image,
                    'phone_number' => $organizer['owner_info']?->phone_number,
                    'mail'         => $organizer['owner_info']?->mail,
                ],
                'banking'    => [
                    'bank_name'           => $organizer['banking']?->bank_name,
                    'account_holder_name' => $organizer['banking']?->account_holder_name,
                    'account_number'      => $organizer['banking']?->account_number
                        ? Str::mask($organizer['banking']->account_number, '*', 2, -3)
                        : null,
                    'routing_number'      => $organizer['banking']?->routing_number,
                    // 'swift_code'          => $organizer['banking']?->swift_code,
                ],
            ]);
        }

        return $data;
    }
}
