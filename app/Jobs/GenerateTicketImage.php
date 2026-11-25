<?php

namespace App\Jobs;

use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;

class GenerateTicketImage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Event $event) {}

    public function handle(): void
    {
        Storage::disk('public')->makeDirectory('events/tickets');
        $savePath = Storage::disk('public')->path('events/tickets/' . $this->event->id . '.jpeg');

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $location = '';
        if ($this->event->is_physical && $this->event->location) {
            $location = $this->event->location;
        }

        $ticket = [
            'id'          => $this->event->id,
            'name'        => $this->event->name,
            'date'        => $this->event->event_date->format('d M, Y'),
            'time'        => Carbon::parse($this->event->start_time)->format('h:i A'),
            'img'         => $this->event->event_banner,
            'location'    => $location,
            'brand_color' => $this->event->brand_color ?? '#000000',
            'org_name'   => $this->event->organizer->organizerProfileData->organization_name ?? '',
        ];

        $html = view('pdf.ticketImgFile', compact('ticket'))->render();

        Browsershot::html($html)
            ->setChromePath(config('laravel-pdf.browsershot.chrome_path'))
            ->setCustomTempPath($tmpDir)
            ->setOption('args', ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'])
            ->waitUntilNetworkIdle()
            ->windowSize(401, 775)
            ->deviceScaleFactor(3)
            ->setScreenshotType('jpeg', 90)
            ->save($savePath);
    }
}
