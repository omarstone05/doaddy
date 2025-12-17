# Digitax Tax Microservice (Penda Suite)

Skeleton for the Digitax/ZRA Smart Invoice integration service.

## Stack
- Laravel API (use same JWT SSO pattern as Budgets)
- DB: MySQL/PostgreSQL
- Cache/Queue: Redis

## Setup (when initializing Laravel)
```bash
composer create-project laravel/laravel .
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
php artisan migrate
```

## Env
See `.env.example` for Digitax/Digitax API keys, JWT secret, queue/webhook settings.

## Endpoints (v1)
- Businesses: CRUD + `/sync-to-digitax`
- Items: CRUD + `/sync`, `/bulk-sync`
- Stock: `/stock/adjust`, `/stock/movements`, `/stock/balance/{itemId}`
- Invoices: create/list/show, `/status`, `/qr-code`, `/retry`, `/bulk`
- Credit/Debit notes: `/invoices/{id}/credit-note`, `/debit-note`
- Reports: sales summary, sync status, failed transactions
- Webhooks: `/api/webhooks/digitax`

## Migrations
SQL drafts are under `database/migrations/sql`. Convert to Laravel migrations on project init.

## Auth
- Accepts JWT from suite apps (`DIGITAX_JWT_SECRET`, `DIGITAX_ALLOWED_ISS`).
- Middleware should upsert organization/user from claims (parent_app/parent_id).

## Queue
- Jobs: SyncItemToDigitax, SyncStockMovement, ProcessInvoice, CheckInvoiceStatus.
- Redis queue + failed_jobs table.
