<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CountryResource;
use App\Http\Resources\PaymentGatewayResource;
use App\Models\Category;
use App\Models\Country;
use App\Models\PaymentGateway;
use Illuminate\Http\JsonResponse;
// use Spatie\Permission\Models\Role;

class LookupController extends Controller
{
    public function countries(): JsonResponse
    {
        $countries = Country::where('is_active', true)->orderBy('name')->get();

        return response()->json(CountryResource::collection($countries));
    }

    public function categories(): JsonResponse
    {
        $categories = Category::where('is_active', true)->get();

        return response()->json(CategoryResource::collection($categories));
    }

    // public function roles(): JsonResponse
    // {
    //     $roles = Role::orderBy('name')->pluck('name');

    //     return response()->json(['data' => $roles]);
    // }

    public function paymentGateways(): JsonResponse
    {
        $gateways = PaymentGateway::where('is_active', true)->get();

        return response()->json(PaymentGatewayResource::collection($gateways));
    }
}