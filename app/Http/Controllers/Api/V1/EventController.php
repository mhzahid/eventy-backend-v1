<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\EventStatus;
use App\Enums\TicketStatus;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\Event\StoreEventRequest;
use App\Http\Requests\Event\UpdateEventRequest;
use App\Http\Resources\EventEditResource;
use App\Http\Resources\EventListResource;
use App\Http\Resources\EventResource;
use App\Http\Resources\OrganizerPublicEventResource;
use App\Models\Event;
use App\Models\Order;
use App\Services\EventService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Number;

class EventController extends Controller
{
    public function __construct(private readonly EventService $eventService) {}

    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = Cache::tags("organizer:{$userId}:events:lists")->remember(
            "organizer:{$userId}:stats",
            600,
            function () use ($request) {
                $user      = $request->user();
                $now       = now();
                $lastMonth = $now->copy()->subMonth();

                $user->loadMissing('organizerProfile.country');
                $country      = $user->organizerProfile?->country;
                $currencyCode = $country?->currency_code;
                $locale       = $country?->locale ?? 'en';

                // Query 1: event-level counts + next date
                $eventStats = $user->events()
                    ->selectRaw('
                        COUNT(*) as total_events,
                        SUM(status = ?) as active_events,
                        SUM(event_date >= ? AND status = ?) as upcoming_events,
                        MIN(CASE WHEN event_date >= ? AND status = ? THEN event_date END) as next_event_date
                    ', [
                        EventStatus::Published->value,
                        $now,
                        EventStatus::Published->value,
                        $now,
                        EventStatus::Published->value,
                    ])->first();


                $eventSubquery = $user->events()->select('id');

                // Query 2: total + this month + last month tickets in one JOIN query
                $ticketStats = DB::table('order_items')
                    ->join('orders', 'order_items.order_id', '=', 'orders.id')
                    ->where('orders.payment_status', 'paid')
                    ->whereIn('orders.event_id', $eventSubquery)
                    ->selectRaw('
                        SUM(order_items.quantity) as total_sold,
                        SUM(CASE WHEN MONTH(orders.created_at) = ? AND YEAR(orders.created_at) = ? THEN order_items.quantity ELSE 0 END) as this_month,
                        SUM(CASE WHEN MONTH(orders.created_at) = ? AND YEAR(orders.created_at) = ? THEN order_items.quantity ELSE 0 END) as last_month
                    ', [$now->month, $now->year, $lastMonth->month, $lastMonth->year])
                    ->first();

                // Query 3: revenue
                $totalRevenue = Order::whereIn('event_id', $eventSubquery)
                    ->where('payment_status', 'paid')
                    ->sum('total_amount');

                $thisMonthSold = (int) ($ticketStats->this_month ?? 0);
                $lastMonthSold = (int) ($ticketStats->last_month ?? 0);

                if ($lastMonthSold > 0) {
                    $rate          = max(0, (($thisMonthSold - $lastMonthSold) / $lastMonthSold) * 100);
                    $ticketsGrowth = ($rate > 0 ? '+' : '') . round($rate) . '% this month';
                } elseif ($thisMonthSold > 0) {
                    $ticketsGrowth = '+100% this month';
                } else {
                    $ticketsGrowth = '0% this month';
                }


                $tSell = $ticketStats->total_sold ?? 0;
                $totalSold = $tSell > 999 ? Number::abbreviate($tSell, 2) :  Number::abbreviate($tSell, 0);

                return [
                    'total_events'       => (int) $eventStats->total_events,
                    'active_events'      => (int) $eventStats->active_events,
                    'total_tickets_sold' => $totalSold,
                    'tickets_growth'     => $ticketsGrowth,
                    'total_revenue'      => Number::currency((int) $totalRevenue, in: $currencyCode, locale: $locale, precision: 0),
                    'upcoming_events'    => (int) $eventStats->upcoming_events,
                    'next_event_date'    => $eventStats->next_event_date
                        ? Carbon::parse($eventStats->next_event_date)->format('M j')
                        : null,
                ];
            }
        );

        return response()->json(['data' => $data]);
    }

    public function store(StoreEventRequest $request): JsonResponse
    {
        $data = $request->validated();

        if ($request->hasFile('event_banner')) {
            $data['event_banner'] = asset('storage/' . $request->file('event_banner')->store('events/banners', 'public'));
        }

        if ($request->hasFile('venue_map_image')) {
            $data['venue_map_image'] = asset('storage/' . $request->file('venue_map_image')->store('events/venue-maps', 'public'));
        }

        if ($request->hasFile('sponsors')) {
            $data['sponsors'] = collect($request->file('sponsors'))
                ->map(function ($file) {
                    $file_path = $file->store('events/sponsors', 'public');
                    return asset('storage/' . $file_path);
                })
                ->all();
        }

        $user = $request->user();

        $this->eventService->create($user, $data);

        Cache::tags("organizer:{$user->id}:events:lists")->flush();

        return response()->json(['message' => 'Event created successfully.'], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $userId = $user->id;
        $cursor = $request->query('cursor', '');
        $cacheKey = "organizer:{$userId}:events:cursor:{$cursor}";

        $user->loadMissing('organizerProfile.country');
        $country = $user->organizerProfile?->country;
        $request->attributes->set('currency_code', $country?->currency_code);
        $request->attributes->set('locale', $country?->locale ?? 'en');

        $data = Cache::tags("organizer:{$userId}:events:lists")->remember($cacheKey, 300, function () use ($request) {
            $user = $request->user();

            $events = $user->events()
                ->select(['id', 'name', 'status', 'event_date', 'location', 'category_id', 'created_at'])
                ->with(['category:id,name'])
                ->withSum('tickets as total_quantity', 'quantity')
                ->withSum('tickets as total_sold', 'sold_quantity')
                ->withSum(['orders as total_revenue' => fn($q) => $q->where('payment_status', 'paid')], 'total_amount')
                ->latest()
                ->cursorPaginate(15);

            $paginated = EventListResource::collection($events)->response()->getData(true);
            $paginated['total_events'] = $user->events()->count();

            return $paginated;
        });

        return response()->json($data);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|ulid|exists:events,id',
        ]);

        $user = $request->user();
        $user->loadMissing('organizerProfile.country');
        $country = $user->organizerProfile?->country;
        $request->attributes->set('currency_code', $country?->currency_code);
        $request->attributes->set('locale', $country?->locale ?? 'en');

        // @formatter:off
        $event = $user->events()
            ->select(['id', 'name', 'slug', 'location', 'map_url', 'venue_map_image', 'is_physical',
                'status', 'event_date', 'start_time', 'sell_end_date','sell_end_time', 'is_age_restricted',
                'charge_transaction_fee', 'brand_color', 'event_banner', 'support_email',
                'support_number', 'facebook_url', 'is_visible', 'is_blocked', 'is_on_hold',
                'is_featured', 'category_id', 'payment_gateway_id', 'user_id', 'created_at'])
        // @formatter:on
            ->with(['category:id,name', 'tickets:id,name,price,sold_quantity,quantity,status,includes,event_id', 'participants:id,name,title,image,event_id', 'paymentGateway:id,name'])
            ->withSum(['orders as total_revenue' => fn($q) => $q->where('payment_status', 'paid')], 'total_amount')
            ->where('id', $id)->firstOrFail();

        return response()->json(['data' => new EventResource($event)]);
    }

    public function update(UpdateEventRequest $request, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);
        $request->validate(['id' => 'required|ulid|exists:events,id']);

        $user  = $request->user();
        $event = $user->events()->findOrFail($id);
        $data  = $request->validated();

        if ($request->hasFile('event_banner')) {
            $data['event_banner'] = asset('storage/' . $request->file('event_banner')->store('events/banners', 'public'));
        } elseif (isset($data['event_banner']) && is_string($data['event_banner'])) {
            // existing URL passed back — keep as-is, already in $data
        } else {
            unset($data['event_banner']);
        }

        if ($request->hasFile('venue_map_image')) {
            $data['venue_map_image'] = asset('storage/' . $request->file('venue_map_image')->store('events/venue-maps', 'public'));
        } elseif (isset($data['venue_map_image']) && is_string($data['venue_map_image'])) {
            // existing URL passed back — keep as-is
        } else {
            unset($data['venue_map_image']);
        }

        if (array_key_exists('sponsors', $data)) {
            $data['sponsors'] = collect($data['sponsors'] ?? [])
                ->map(fn($sponsor) => $sponsor instanceof UploadedFile
                    ? asset('storage/' . $sponsor->store('events/sponsors', 'public'))
                    : $sponsor)
                ->values()
                ->all() ?: null;
        }

        $oldSlug = $event->slug;

        $this->eventService->update($event, $data);

        Cache::tags("organizer:{$user->id}:events:lists")->flush();
        Cache::forget("event:public:{$oldSlug}");
        if ($event->slug !== $oldSlug) {
            Cache::forget("event:public:{$event->slug}");
        }

        return response()->json(['message' => 'Event updated successfully.']);
    }

    public function edit(Request $request, string $id): JsonResponse
    {
        $request->merge(['id' => $id]);

        $request->validate([
            'id' => 'required|ulid|exists:events,id',
        ]);

        $user = $request->user();


        $event = $user->events()
            ->with(['category:id,name', 'tickets:id,name,price,quantity,includes,event_id', 'participants:id,name,title,image,event_id', 'paymentGateway:id,name', 'form:id,event_id,fields'])
            ->where('id', $id)->firstOrFail();

        return response()->json(['data' => new EventEditResource($event)]);
    }

    public function chart(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $data = Cache::tags("organizer:{$userId}:events:lists")->remember(
            "organizer:{$userId}:chart",
            600,
            function () use ($userId) {
                $now = now();

                // Daily: last 7 days — simple lookup on tiny summary table
                $dailyRaw = DB::table('organizer_stats_daily')
                    ->where('organizer_id', $userId)
                    ->where('stat_date', '>=', $now->copy()->subDays(6)->toDateString())
                    ->pluck('total_sold', 'stat_date');

                $dailyLabels = [];
                $dailyValues = [];

                for ($i = 6; $i >= 0; $i--) {
                    $day           = $now->copy()->subDays($i);
                    $dailyLabels[] = $day->format('M j');
                    $dailyValues[] = (int) ($dailyRaw[$day->toDateString()] ?? 0);
                }

                // Monthly: last 12 months
                $monthlyStart = $now->copy()->subMonths(11);
                $monthlyRaw   = DB::table('organizer_stats_monthly')
                    ->where('organizer_id', $userId)
                    ->whereRaw('(year * 100 + month) >= ?', [$monthlyStart->year * 100 + $monthlyStart->month])
                    ->get()
                    ->keyBy(fn($row) => $row->year . '-' . $row->month);

                $monthlyLabels = [];
                $monthlyValues = [];

                for ($i = 11; $i >= 0; $i--) {
                    $month           = $now->copy()->subMonths($i);
                    $monthlyLabels[] = $month->format('M Y');
                    $monthlyValues[] = (int) ($monthlyRaw[$month->year . '-' . $month->month]->total_sold ?? 0);
                }

                // Yearly: all time
                $yearlyRaw = DB::table('organizer_stats_yearly')
                    ->where('organizer_id', $userId)
                    ->pluck('total_sold', 'year');

                $yearlyLabels = [];
                $yearlyValues = [];

                if ($yearlyRaw->isNotEmpty()) {
                    $minYear = (int) $yearlyRaw->keys()->min();
                    for ($year = $minYear; $year <= $now->year; $year++) {
                        $yearlyLabels[] = (string) $year;
                        $yearlyValues[] = (int) ($yearlyRaw[$year] ?? 0);
                    }
                }

                return [
                    'daily' => [
                        'labels' => $dailyLabels,
                        'values' => $dailyValues,
                        'total'  => array_sum($dailyValues),
                    ],
                    'monthly' => [
                        'labels' => $monthlyLabels,
                        'values' => $monthlyValues,
                        'total'  => array_sum($monthlyValues),
                    ],
                    'yearly' => [
                        'labels' => $yearlyLabels,
                        'values' => $yearlyValues,
                        'total'  => array_sum($yearlyValues),
                    ],
                ];
            }
        );

        return response()->json(['data' => $data]);
    }

    public function updateTicketStatus(Request $request): JsonResponse
    {
        $request->validate([
            'event_id'  => 'required|ulid|exists:events,id',
            'ticket_id' => 'required|ulid|exists:tickets,id',
            'status'    => ['required', Rule::enum(TicketStatus::class)],
        ]);

        $user   = $request->user();
        $event  = $user->events()->findOrFail($request->event_id);
        $ticket = $event->tickets()->findOrFail($request->ticket_id);

        $ticket->update(['status' => $request->status]);

        return response()->json(['message' => 'Ticket status updated.']);
    }


    public function showPublic (Request $request, string $slug): JsonResponse
    {
        $event = Event::where('slug', $slug)
            ->with([
                'category:id,name',
                'paymentGateway:id,name',
                'participants:id,name,title,image,event_id',
                'tickets' => fn($q) => $q
                    ->where('status', TicketStatus::Active)
                    ->select(['id', 'name', 'price', 'quantity', 'sold_quantity', 'includes', 'event_id']),
                'form:id,event_id,fields',
                'organizer:id',
                'organizer.organizerProfile:user_id,country_id',
                'organizer.organizerProfile.country:id,currency_code,locale',
            ])
            ->first();

        if (!$event) {
            return response()->json(['message' => 'Event not found.'], 404);
        }

        $country = $event->organizer?->organizerProfile?->country;
        $request->attributes->set('currency_code', $country?->currency_code);
        $request->attributes->set('locale', $country?->locale ?? 'en');

        $data = (new OrganizerPublicEventResource($event))->toArray($request);

        return response()->json(['data' => $data]);
    }

}