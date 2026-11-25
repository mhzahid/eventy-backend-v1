<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Spatie\Browsershot\Browsershot;
use Carbon\Carbon;
use function Spatie\LaravelPdf\Support\pdf;
use Illuminate\Support\Str;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\SvgWriter;


Route::get('/', function () {
    // $cipher = "AES-192-CTR";
    // $options = 0;
    // $encryption_iv = 'c84eac14f30565616393488c5e7cc48c';
    // $encryption_key = env('APP_KEY');

    // $encryption = openssl_encrypt(
    //     'DMF26-R1RGSAFV>>01krjjeztxa8nj8dpqdrg7x2mv',
    //     $cipher,
    //     $encryption_key,
    //     $options,
    //     hex2bin($encryption_iv)
    // );

    // $decryptData = openssl_decrypt(
    //     $encryption,
    //     $cipher,
    //     $encryption_key,
    //     $options,
    //     hex2bin($encryption_iv)
    // );

    return [
        'message' => 'Welcome to the Eventy Event Management API',
        'version' => '1.0',
        // 'id' => $encryption,
        // 'id2' => $decryptData,
    ];
});

Route::get('*', function () {
    return abort(404);
})->where('any', '.*');

// Route::get('/pdf', function () {
//     $path = asset('storage/events/tickets/01kt4dymdp90a29r7k2x7z2a83.jpeg');
//     $img  = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($path));

//     $ticket = [
//         'id' => 'UNN3-7EGAd73a',
//         'img' => $img,
//         'type' => 'General',
//         'name' => 'Mh zahid',
//         'brand_color' => "#098b85",
//         'qr'   => genQR(),
//     ];

//     // return view('pdf.ticket', compact('ticket'));

//     return pdf()
//         ->view('pdf.ticket', compact('ticket'))
//         ->paperSize(106.099, 205, 'mm')
//         ->margins(0, 0, 0, 0)
//         ->tagged()
//         ->meta(
//             title: 'Ticket #' . $ticket['id'],
//             author: config('app.name'),
//             subject: 'Event Ticket',
//             keywords: 'ticket, event, ' . now()->monthName . '-' . now()->year,
//             creator: config('app.name') . ' Web Platform',
//             creationDate: now(),
//         )
//         ->withBrowsershot(function (Browsershot $browsershot) {
//             return $browsershot->writeOptionsToFile();
//         })
//         ->name('ticket-' . $ticket['id'] . '.pdf');
// });


// Route::get('/img', function () {

//     $this->event = \App\Models\Event::find('01kt4dymdp90a29r7k2x7z2a83');

//     $savePath = Storage::disk('public')->path('events/tickets/' . $this->event->id . '.jpeg');

//     $location = '';
//     if ($this->event->is_physical && $this->event->location) {
//         $location = $this->event->location;
//     }

//     $ticket = [
//         'id'          => $this->event->id,
//         'name'        => $this->event->name,
//         'date'        => $this->event->event_date->format('d M, Y'),
//         'time'        => Carbon::parse($this->event->start_time)->format('h:i A'),
//         'img'         => $this->event->event_banner,
//         'location'    => $location,
//         'brand_color' => $this->event->brand_color ?? '#000000',
//         'org_name'   => $this->event->organizer->organizerProfileData->organization_name ?? '',
//     ];

//     // return view('pdf.ticketImgFile', compact('ticket'))->render();

//     $html = view('pdf.ticketImgFile', compact('ticket'))->render();

//     Browsershot::html($html)
//         ->setChromePath(config('laravel-pdf.browsershot.chrome_path'))
//         ->noSandbox()
//         ->setOption('args', ['--no-sandbox', '--disable-gpu', '--disable-dev-shm-usage'])
//         ->windowSize(401, 775)
//         ->deviceScaleFactor(3)
//         ->setScreenshotType('jpeg', 90)
//         ->save($savePath);

//     return response()->file($savePath);
// });


// Route::get('/qr', function () {
//     return genQR();
// });


// function genQR()
// {
//     $data = "UNN3-7EGAd73a#01kt4dymdp90a29r7k2x7z2a83";

//     $encyData = openssl_encrypt(
//         $data,
//         config('services.ticket_qr.cipher_algorithm'),
//         config('app.key'),
//         0,
//         hex2bin(config('services.ticket_qr.encryption_iv'))
//     );

//     $writer = new SvgWriter();

//     $qrCode = new QrCode(
//         data: $encyData,
//         encoding: new Encoding('UTF-8'),
//         errorCorrectionLevel: ErrorCorrectionLevel::Medium,
//         size: 210,
//         margin: 2,
//         roundBlockSizeMode: RoundBlockSizeMode::None,
//         foregroundColor: new Color(0, 0, 0),
//         backgroundColor: new Color(255, 255, 255, 127)
//     );

//     return $writer->write($qrCode)->getString();
// }