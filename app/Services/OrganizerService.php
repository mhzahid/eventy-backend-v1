<?php

namespace App\Services;

use App\Models\OrganizerBanking;
use App\Models\OrganizerOwnerInfo;
use App\Models\User;

class OrganizerService
{
    public function updateProfile(User $user, array $data): void
    {
        if (isset($data['contact_number'])) {
            $user->update(['phone' => $data['contact_number']]);
            unset($data['contact_number']);
        }

        $user->organizerProfile()->update($data);
    }

    public function saveOwnerInfo(User $user, array $data): OrganizerOwnerInfo
    {
        return $user->organizerOwnerInfo()->updateOrCreate([], $data);
    }

    public function storeBankingInfo(User $user, array $data): OrganizerBanking
    {
        return $user->organizerBanking()->create($data);
    }
}