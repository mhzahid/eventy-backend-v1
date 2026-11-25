<?php

namespace App\Services;

use App\Jobs\GenerateTicketImage;
use App\Models\Event;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EventService
{
    public function create(User $user, array $data): Event
    {
        $attempt = 0;
        retry:
        try {
        $event = DB::transaction(function () use ($user, $data) {
            $event = $user->events()->create([
                'name'                   => $data['name'],
                'slug'                   => $data['slug'],
                // 'tag_line'               => $data['tag_line'],
                'prefix'                 => $this->generatePrefix($data['name']),
                'description'            => $data['description'],
                'location'               => $data['location'],
                'map_url'                => $data['map_url'],
                'is_physical'            => $data['is_physical'],
                'event_date'             => $data['event_date'],
                'start_time'             => $data['start_time'],
                'sell_end_date'          => $data['sell_end_date'] ?? $data['event_date'],
                'sell_end_time'          => $data['sell_end_time'] ?? '23:59:00',
                'category_id'            => $data['category_id'],
                'payment_gateway_id'     => $data['payment_gateway_id'],
                'is_age_restricted'      => $data['is_age_restricted'] ?? false,
                'charge_transaction_fee' => $data['charge_transaction_fee'] ?? false,
                'max_tickets_per_order'  => $data['max_tickets_per_order'] ?? 5,
                'brand_color'            => $data['brand_color'],
                'event_banner'           => $data['event_banner'] ?? null,
                'support_email'          => $data['support_email'],
                'support_number'         => $data['support_number'],
                'facebook_url'           => $data['facebook_url'],
                'terms_and_conditions'   => $data['terms_and_conditions'],
                'sponsors'               => !empty($data['sponsors']) ? $data['sponsors'] : null,
                'highlights'             => $data['highlights'],
                'status'                 => $data['status'],
                'venue_map_image'        => $data['venue_map_image'] ?? null,
                // 'is_free'                => collect($data['tickets'])->every(fn($t) => ($t['price'] ?? 0) == 0),
            ]);

            foreach ($data['tickets'] as $ticket) {
                $event->tickets()->create($ticket);
            }

            foreach ($data['participants'] ?? [] as $participant) {
                $event->participants()->create([
                    'name'  => $participant['name'],
                    'title' => $participant['title'],
                    'image' => isset($participant['image'])
                        ? (asset('storage/' . $participant['image']->store('events/participants', 'public')))
                        : null,
                ]);
            }

            if (!empty($data['form_fields'])) {
                $event->form()->create(['fields' => $data['form_fields']]);
            }

            return $event;
        });
        } catch (UniqueConstraintViolationException $e) {
            if (++$attempt < 5) goto retry;
            throw $e;
        }

        // Generate initial ticket image
        GenerateTicketImage::dispatch($event);

        return $event;
    }


    public function update(Event $event, array $data): void
    {
        $filesToDelete = [];

        // Collect old event_banner if being replaced
        if (array_key_exists('event_banner', $data) && $event->event_banner && $event->event_banner !== $data['event_banner']) {
            $filesToDelete[] = $event->event_banner;
        }

        // Collect old venue_map_image if being replaced
        if (array_key_exists('venue_map_image', $data) && $event->venue_map_image && $event->venue_map_image !== $data['venue_map_image']) {
            $filesToDelete[] = $event->venue_map_image;
        }

        // Collect removed sponsor URLs
        if (array_key_exists('sponsors', $data)) {
            $newUrls         = $data['sponsors'] ?? [];
            $filesToDelete   = array_merge($filesToDelete, array_diff($event->sponsors ?? [], $newUrls));
        }

        DB::transaction(function () use ($event, $data, &$filesToDelete) {
            $event->update(array_filter([
                'name'                   => $data['name'],
                'slug'                   => $data['slug'],
                // 'tag_line'               => $data['tag_line'],
                'description'            => $data['description'],
                'status'                 => $data['status'],
                'is_physical'            => $data['is_physical'],
                'location'               => $data['location'],
                'map_url'                => $data['map_url'],
                'event_date'             => $data['event_date'],
                'start_time'             => $data['start_time'],
                'sell_end_date'          => $data['sell_end_date'] ?? $data['event_date'],
                'sell_end_time'          => $data['sell_end_time'] ?? '23:59:00',
                'category_id'            => $data['category_id'],
                'payment_gateway_id'     => $data['payment_gateway_id'],
                'is_age_restricted'      => $data['is_age_restricted'],
                'charge_transaction_fee' => $data['charge_transaction_fee'],
                'max_tickets_per_order'  => $data['max_tickets_per_order'],
                'brand_color'            => $data['brand_color'],
                'event_banner'           => $data['event_banner'] ?? null,
                'venue_map_image'        => $data['venue_map_image'] ?? null,
                'support_email'          => $data['support_email'],
                'support_number'         => $data['support_number'],
                'facebook_url'           => $data['facebook_url'],
                'terms_and_conditions'   => $data['terms_and_conditions'],
                'highlights'             => $data['highlights'],
                'sponsors'               => array_key_exists('sponsors', $data) ? ($data['sponsors'] ?: null) : null,
            ], fn($v, $k) => array_key_exists($k, $data), ARRAY_FILTER_USE_BOTH));

            if (isset($data['tickets'])) {
                $submittedIds = collect($data['tickets'])->pluck('id')->filter()->all();

                $event->tickets()
                    ->whereNotIn('id', $submittedIds)
                    ->doesntHave('orderItems')
                    ->delete();

                foreach ($data['tickets'] as $ticket) {
                    if (!empty($ticket['id'])) {
                        $event->tickets()->where('id', $ticket['id'])->update([
                            'name'     => $ticket['name'],
                            'price'    => $ticket['price'],
                            'quantity' => $ticket['quantity'],
                            'includes' => $ticket['includes'] ?? null,
                        ]);
                    } else {
                        $event->tickets()->create($ticket);
                    }
                }
            }

            if (array_key_exists('participants', $data)) {
                $submittedIds = collect($data['participants'] ?? [])->pluck('id')->filter()->all();

                // Collect images of removed participants for deletion
                $event->participants()->whereNotIn('id', $submittedIds)->pluck('image')
                    ->filter()->each(fn($img) => $filesToDelete[] = $img);

                $event->participants()->whereNotIn('id', $submittedIds)->delete();

                foreach ($data['participants'] ?? [] as $participant) {
                    $newImage = $this->resolveParticipantImage($participant['image'] ?? null);

                    if (!empty($participant['id'])) {
                        $existing = $event->participants()->find($participant['id']);

                        if (!$existing) {
                            continue;
                        }

                        // Collect old image if being replaced
                        if ($newImage && $existing->image && $newImage !== $existing->image) {
                            $filesToDelete[] = $existing->image;
                        }

                        $existing->update([
                            'name'  => $participant['name'],
                            'title' => $participant['title'],
                            'image' => $newImage ?? $existing->image,
                        ]);
                    } else {
                        $event->participants()->create([
                            'name'  => $participant['name'],
                            'title' => $participant['title'],
                            'image' => $newImage,
                        ]);
                    }
                }
            }

            if (array_key_exists('form_fields', $data)) {
                $event->form()->updateOrCreate(
                    ['event_id' => $event->id],
                    ['fields'   => $data['form_fields'] ?: null],
                );
            }
        });

        // Regenerate ticket image if it was updated
        GenerateTicketImage::dispatch($event);

        // Delete old files after transaction commits
        foreach ($filesToDelete as $url) {
            $this->deleteFile($url);
        }
    }

    private function deleteFile(string $url): void
    {
        $path = ltrim(str_replace(asset('storage'), '', $url), '/');
        Storage::disk('public')->delete($path);
    }

    private function resolveParticipantImage(mixed $image): ?string
    {
        if (is_null($image)) return null;
        if (is_string($image)) return $image;
        return asset('storage/' . $image->store('events/participants', 'public'));
    }

    private const PREPOSITIONS = ['a', 'an', 'and', 'or', 'but', 'of', 'in', 'at', 'by', 'for', 'with', 'on', 'to', 'from', 'into', 'onto', 'upon', 'over', 'below', 'about', 'against', 'among', 'between', 'during', 'before', 'after', 'without', 'within', 'along', 'behind', 'beside', 'beyond', 'down', 'up', 'off', 'out', 'near', 'since', 'until', 'via', 'per', 'as', 'than', 'plus', 'vs',];


    private function generatePrefix(string $name): string
    {
        $allWords = array_values(array_filter(preg_split('/\s+/', trim($name)), fn($w) => ctype_alpha($w[0])));
        $words    = array_values(array_filter($allWords, fn($w) => !in_array(strtolower($w), self::PREPOSITIONS)));

        // Fall back to all words if filtering removed everything
        if (empty($words)) {
            $words = $allWords;
        }
        $initials = strtoupper(implode('', array_map(fn($w) => $w[0], $words)));

        preg_match_all('/\d+/', $name, $m);
        $digits = implode('', array_map(fn($n) => substr($n, -2), $m[0]));

        $raw   = $initials . $digits;
        $pad   = strtoupper(substr($words[0] ?? '', 1)) ?: 'X';
        $base  = substr(str_pad($raw, 5, $pad), 0, 5);
        $start = $digits !== '' ? max(3, min(strlen($raw), 5)) : 3;

        for ($len = $start; $len <= 5; $len++) {
            $prefix = substr($base, 0, $len);
            if (!Event::where('prefix', $prefix)->exists()) {
                return $prefix;
            }
        }

        $count = 1;
        do {
            $suffix = (string) $count++;
            $prefix = substr($base, 0, 5 - strlen($suffix)) . $suffix;
        } while (Event::where('prefix', $prefix)->exists());

        return $prefix;
    }
}
