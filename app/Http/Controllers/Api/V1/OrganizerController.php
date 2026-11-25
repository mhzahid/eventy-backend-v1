<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Organizer\SaveOwnerInfoRequest;
use App\Http\Requests\Organizer\StoreBankingInfoRequest;
use App\Http\Requests\Organizer\UpdateProfileRequest;
use App\Services\OrganizerService;
use Illuminate\Http\JsonResponse;

class OrganizerController extends Controller
{
    public function __construct(private readonly OrganizerService $organizerService) {}

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('logo')) {
            $data['logo'] = asset('storage/' . $request->file('logo')->store('organizers/logos', 'public'));
        } elseif (isset($data['logo']) && is_string($data['logo'])) {
            // existing URL passed back — keep as-is
        } else {
            unset($data['logo']);
        }

        $this->organizerService->updateProfile($user, $data);

        return response()->json(['message' => 'Profile updated successfully.']);
    }

    public function saveOwnerInfo(SaveOwnerInfoRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('id_image')) {
            $data['id_image'] = asset('storage/' . $request->file('id_image')->store('organizers/id-images', 'public'));
        } elseif (isset($data['id_image']) && is_string($data['id_image'])) {
            // existing URL passed back — keep as-is
        } else {
            unset($data['id_image']);
        }

        $this->organizerService->saveOwnerInfo($user, $data);

        return response()->json(['message' => 'Owner information saved successfully.']);
    }

    public function storeBankingInfo(StoreBankingInfoRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->organizerBanking()->exists()) {
            return response()->json(['message' => 'Banking information has already been set and cannot be changed.'], 409);
        }

        $this->organizerService->storeBankingInfo($user, $request->validated());

        return response()->json(['message' => 'Banking information saved successfully.'], 201);
    }
}