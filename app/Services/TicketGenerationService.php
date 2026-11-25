<?php

namespace App\Services;

use App\Mail\TicketMail;
use App\Models\Attendee;
use App\Models\Event;
use App\Models\Order;
use Illuminate\Support\Facades\Mail;
use Spatie\Browsershot\Browsershot;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;
use Illuminate\Support\Facades\Log;
use function Spatie\LaravelPdf\Support\pdf;

class TicketGenerationService
{
    public function generateGuestPdfs(array $attendees, string $ticketName, Event $event): array
    {
        $img = asset('storage/events/tickets/' . $event->id . '.jpeg');
        $brandColor = $event->brand_color ?? '#000000';
        $pdfAttachments = [];

        foreach ($attendees as $attendee) {
            $pdfContent = $this->generatePdf($attendee, $ticketName, $img, $brandColor);

            $pdfAttachments[] = [
                'content'  => $pdfContent,
                'filename' => $attendee->ticket_code . '.pdf',
            ];
        }

        return $pdfAttachments;
    }

    public function generateForOrder(Order $order): void
    {
        $order->loadMissing([
            'user:id,name,email',
            'event:id,name,event_date,start_time,is_physical,location,brand_color',
            'items.ticket:id,name',
            'items.attendees',
        ]);

        $event = $order->event;
        $pdfAttachments = [];

        $img = asset('storage/events/tickets/' . $event->id . '.jpeg');

        foreach ($order->items as $item) {
            $ticketName = $item->ticket->name ?? 'General';

            foreach ($item->attendees as $attendee) {
                $pdfContent = $this->generatePdf($attendee, $ticketName, $img, $event->brand_color ?? '#000000');

                $pdfAttachments[] = [
                    'content'  => $pdfContent,
                    'filename' => $attendee->ticket_code . '.pdf',
                ];
            }
        }

        if (empty($pdfAttachments)) {
            Log::warning('TicketGeneration: no PDFs generated — no attendees found', ['order_id' => $order->id]);
            return;
        }

        Mail::to($order->user->email)->send(new TicketMail(
            buyer: $order->user,
            event: $event,
            pdfAttachments: $pdfAttachments,
        ));
    }

    private function generatePdf(Attendee $attendee, string $ticketName, string $img, string $brandColor): string
    {
        $qrSvg = $this->generateQrCode($attendee->ticket_code . '#' . $attendee->event_id);

        $ticket = [
            'id'   => $attendee->ticket_code,
            'name' => $attendee->name,
            'img'  => $img,
            'qr'   => $qrSvg,
            'type' => $ticketName,
            'brand_color' => $brandColor,
        ];

        return base64_decode(
            pdf()
                ->view('pdf.ticket', compact('ticket'))
                ->paperSize(106.099, 205, 'mm')
                ->margins(0, 0, 0, 0)
                ->tagged()
                ->meta(
                    title: 'Ticket #' . $ticket['id'],
                    author: config('app.name'),
                    subject: 'Event Ticket',
                    keywords: 'ticket, event, pass, ' . $ticket['type'] . ', ' . now()->monthName . '-' . now()->year,
                    creator: config('app.name') . ' Web Platform',
                    creationDate: now(),
                )
                ->withBrowsershot(function (Browsershot $browsershot) {
                    $tmpDir = storage_path('app/tmp');
                    if (!is_dir($tmpDir)) {
                        mkdir($tmpDir, 0755, true);
                    }
                    return $browsershot
                        ->setChromePath(config('laravel-pdf.browsershot.chrome_path'))
                        ->setCustomTempPath($tmpDir)
                        ->setOption('args', ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'])
                        ->waitUntilNetworkIdle()
                        ->writeOptionsToFile();
                })
                ->base64()
        );
    }

    private function generateQrCode(string $data): string
    {
        if (empty($data)) {
            return '';
        }

        $encyData = openssl_encrypt(
            $data,
            config('services.ticket_qr.cipher_algorithm'),
            config('app.key'),
            0,
            hex2bin(config('services.ticket_qr.encryption_iv'))
        );

        $writer = new SvgWriter();

        $qrCode = new QrCode(
            data: $encyData,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::Medium,
            size: 210,
            margin: 2,
            roundBlockSizeMode: RoundBlockSizeMode::None,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255, 127)
        );

        return $writer->write($qrCode)->getString();
    }
}
