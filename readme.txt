# Order Management System

## Tech Stack
- Laravel 11
- MySQL
- Redis
- Laravel Sanctum

## Setup Instructions
1. Clone the repo
2. Run `composer install`
3. Copy `.env.example` to `.env`
4. Set database credentials in `.env`
5. Run `php artisan migrate`
6. Run `php artisan serve`

## API Endpoints
- POST /api/v1/register
- POST /api/v1/login
- GET  /api/v1/wallet
- POST /api/v1/wallet/topup
- POST /api/v1/orders
- GET  /api/v1/orders
- GET  /api/v1/orders/{id}
- PATCH /api/v1/orders/{id}/status
- POST /api/v1/logout

## How Duplicate Orders Are Prevented
Every order request requires a unique idempotency_key.
If same key is sent twice, original response is returned
without creating a new order or deducting wallet again.

## What Happens If Server Crashes During Payment?
All wallet deduction and order creation is wrapped inside
DB::transaction(). If anything fails, everything rolls back
automatically. No money is lost.