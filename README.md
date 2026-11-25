# Eventy Backend v1

Eventy is an API backend for an event ticketing platform. Organizers can publish events and manage tickets, while customers can place orders and pay through supported gateways.

> ## **This repository is the v1 MVP.** For the full v2 version, please contact me.

## Features

- User OTP authentication, plus organizer, admin, and ticket-scanner authentication
- Organizer profiles, event and ticket management, attendee invitations, and scanner accounts
- Public event details and customer checkout
- bKash and SSLCommerz payment callbacks and IPN handling
- Organizer balance, withdrawal, and transaction endpoints
- Ticket scanning, order refunds, and organizer sales statistics

## Tech stack

- PHP 8.3+
- Laravel 13
- MySQL
- Laravel Sanctum and Spatie Laravel Permission
- Redis for caching (as configured by default)
- Database queue for background jobs (as configured by default)

## Getting started

### Requirements

Install PHP 8.3 or later, Composer, Node.js/npm, MySQL, and Redis. Configure a database and Redis instance before running the application.

### Install

```bash
composer install
```

Create your local environment file:

```powershell
# Windows PowerShell
Copy-Item .env.example .env
```

```bash
# macOS / Linux
cp .env.example .env
```

Update `.env` with your database and Redis settings. Add the credentials for the payment gateways you plan to use; the example configuration uses sandbox mode. Then run:

```bash
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Run locally

Start the API server:

```bash
php artisan serve
```

The API is available at `http://localhost:8000/api`. The versioned endpoints use the `/api/v1` prefix.

In separate terminals, run the queue worker and local scheduler:

```bash
php artisan queue:work
php artisan schedule:work
```

The scheduler releases expired orders every minute, reconciles organizer statistics nightly, and prunes expired Sanctum tokens daily. In production, configure Laravel's scheduler to run `php artisan schedule:run` every minute and run a queue worker under a process manager.

## Tests

Run the test suite with:

```bash
php artisan test
```
