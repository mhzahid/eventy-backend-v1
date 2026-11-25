<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\AdminAuthController;
use App\Http\Controllers\Api\Auth\OrganizerAuthController;
use App\Http\Controllers\Api\Auth\OtpAuthController;
use App\Http\Controllers\Api\Auth\ScannerAuthController;
use App\Http\Controllers\Api\V1\OrganizerController;
use App\Http\Controllers\Api\V1\OrderRefundController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\IpnController;
use App\Http\Controllers\Api\V1\PaymentCallbackController;
use App\Http\Controllers\Api\V1\EventController;
use App\Http\Controllers\Api\V1\AttendeeController;
use App\Http\Controllers\Api\V1\InviteController;
use App\Http\Controllers\Api\V1\LookupController;
use App\Http\Controllers\Api\V1\OrganizerBalanceController;
use App\Http\Controllers\Api\V1\OrganizerScannerController;
use App\Http\Controllers\Api\V1\PublicEventController;
use App\Http\Controllers\Api\V1\TicketScanController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;


// User OTP Auth
Route::prefix('auth')->group(function () {
    Route::controller(OtpAuthController::class)->prefix('otp')->group(function () {
        Route::post('/send', 'send');
        Route::post('/verify', 'verify');
    });

    // Organizer Auth
    Route::controller(OrganizerAuthController::class)->prefix('organizer')->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

    // Admin Auth
    Route::controller(AdminAuthController::class)->prefix('admin')->group(function () {
        // Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

    // Scanner Auth
    Route::post('/scanner/login', [ScannerAuthController::class, 'login']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});


Route::prefix('v1')->group(function () {
    // Lookup / reference data (public)
    Route::get('/countries', [LookupController::class, 'countries']);

    // Orders (public — no auth required for checkout)
    Route::post('/orders', [OrderController::class, 'store'])->middleware(['allowed.ips', 'throttle:8,1']);

    // Payment gateway callbacks (called by SSLCommerz / bKash — no auth)
    Route::match(['get', 'post'], '/payment/callback/{gateway}/{status}', [PaymentCallbackController::class, 'handle'])
        ->name('payment.callback'); // @internal

    // IPN — server-to-server notification from payment gateway
    Route::post('/payment/ipn/{gateway}', [IpnController::class, 'handle'])
        ->name('payment.ipn'); // @internal
    // https://api.eventy.com/api/v1/payment/ipn/sslcommerz
    // https://api.eventy.com/api/v1/payment/ipn/bkash


    Route::get('/view/event/{slug}', [PublicEventController::class, 'show'])->middleware(['allowed.ips', 'throttle:30,1']);


    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::controller(UserController::class)->prefix('user')->group(function () {
            Route::get('/', 'info');
            Route::put('/update/name', 'updateName');
            Route::put('/update/email', 'updateEmail');
            Route::put('/update/password', 'updatePassword');
        });

        // Route::get('/roles', [LookupController::class, 'roles']);

        // Organizer-only routes
        Route::middleware('role:organizer')->prefix('organizer')->group(function () {

            // Lookup / reference data

            Route::controller(LookupController::class)->group(function () {
                Route::get('/categories',  'categories');
                Route::get('/payment-gateways', 'paymentGateways');
            });


            Route::controller(OrganizerController::class)->group(function () {
                Route::put('/profile', 'updateProfile');
                Route::put('/owner-info', 'saveOwnerInfo');
                Route::post('/banking-info', 'storeBankingInfo');
            });

            Route::controller(EventController::class)->prefix('event')->group(function () {
                Route::get('/statistics', 'stats');
                Route::get('/chart', 'chart');
                Route::get('/list', 'index');
                Route::post('/create', 'store');
                Route::get('/show/{id}', 'show');
                Route::get('/edit/{id}', 'edit');
                Route::put('/update/{id}', 'update');
                Route::patch('/ticket/status', 'updateTicketStatus');

                Route::get('/view/{slug}', 'showPublic'); // Organizer's view of their own event (includes unpublished/hidden details)

                // Route::delete('/delete/{id}', 'destroy');
            });

            Route::controller(OrganizerBalanceController::class)->group(function () {
                Route::get('/balance', 'show');
                Route::post('/withdraw', 'withdraw');
                Route::get('/transactions', 'transactions');
            });

            Route::post('/event/{eventId}/invite', [InviteController::class, 'store']);
            Route::get('/event/{eventId}/attendees', [AttendeeController::class, 'index']);

            Route::controller(OrganizerScannerController::class)->prefix('scanners')->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::patch('/change/password', 'updatePassword');
                Route::delete('/remove', 'destroy');
            });
        });

        // Organizer and ticket scanner
        Route::middleware('role:ticket-scanner|organizer')->group(function () {
            Route::controller(TicketScanController::class)->prefix('scan')->group(function () {
                Route::get('/event/list', 'eventListForScanner');
                Route::post('/event', 'scanTicket');
            });
        });


        Route::middleware('role:super-admin|admin')->group(function () {

            // Order Refund
            Route::controller(OrderRefundController::class)->group(function () {
                Route::post('/refund/order', 'refund');
            });
        });
    });


    // Route::group(['middleware' => ['role:manager|writer']], function () { ... });
});