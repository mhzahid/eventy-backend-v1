<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\PublicEventResource;
use App\Http\Resources\PublicTicketResource;
use App\Models\Event;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class PublicEventController extends Controller
{
    public function show(Request $request, string $slug): JsonResponse
    {
        $cacheKey = "event:public:{$slug}";
        $cached   = Cache::get($cacheKey);

        if ($cached !== null) {
            $request->attributes->set('currency_code', $cached['currency_code'] ?? null);
            $request->attributes->set('locale', $cached['_locale'] ?? 'en');


            $timezone = $cached['_timezone'] ?? 'UTC';
            $sale_status = $this->resolveEventStatus($cached['sell_end_time'], $cached['sell_end_date'], $timezone);
            $cached['sale_status']  = $sale_status;
            $cached['is_past']      = Carbon::parse($cached['event_date'])->lt(Carbon::today($timezone));
            $cached['tickets']      = $cached['sale_status']  === 'ended' ? [] : $this->liveTickets($request, $cached['id']);
            unset($cached['_locale'], $cached['_timezone']);

            return response()->json(['data' => $cached]);
        }

        $event = Event::where('slug', $slug)
            ->where('status', EventStatus::Published)
            ->where('is_visible', true)
            ->where('is_blocked', false)
            ->where('is_on_hold', false)
            ->with([
                'category:id,name',
                'paymentGateway:id,name',
                'participants:id,name,title,image,event_id',
                'form:id,event_id,fields',
                'organizer:id',
                'organizer.organizerProfile:user_id,country_id,organization_name',
                'organizer.organizerProfile.country:id,currency_code,locale,timezone',
            ])
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $country      = $event->organizer?->organizerProfile?->country;
        $currencyCode = $country?->currency_code;
        $locale       = $country?->locale ?? 'en';
        $timezone     = $country?->timezone ?? 'UTC';

        $request->attributes->set('currency_code', $currencyCode);
        $request->attributes->set('locale', $locale);

        $data               = (new PublicEventResource($event))->response($request)->getData(true)['data'];
        $data['_locale']    = $locale;
        $data['_timezone']  = $timezone;

        Cache::put($cacheKey, $data, now()->addMinutes(15));

        $sale_status         = $this->resolveEventStatus($event->sell_end_time, $event->sell_end_date->toDateString(), $timezone);
        $data['sale_status'] = $sale_status;
        $data['is_past']     = $event->event_date->lt(Carbon::today($timezone));
        $data['tickets']     = $sale_status === 'ended' ? [] : $this->liveTickets($request, $event->id);
        unset($data['_locale'], $data['_timezone']);

        return response()->json(['data' => $data]);
    }

    private function resolveEventStatus(string $sellEndTime, string $sellEndDate, string $timezone): string
    {
        $sellDeadline = Carbon::parse($sellEndDate . ' ' . $sellEndTime, $timezone);

        return $sellDeadline->lt(Carbon::now($timezone)) ? 'ended' : 'on_sale';
    }

    private function liveTickets(Request $request, string $eventId): array
    {
        $tickets = Ticket::where('event_id', $eventId)
            ->where('status', TicketStatus::Active)
            ->select(['id', 'name', 'price', 'quantity', 'sold_quantity', 'includes', 'event_id'])
            ->get();

        return PublicTicketResource::collection($tickets)->resolve($request);
    }
}