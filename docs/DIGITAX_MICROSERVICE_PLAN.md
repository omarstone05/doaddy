# Digitax Tax Microservice (Penda Suite)

Handles all ZRA Smart Invoice compliance via the Digitax API for Addy, Projjo, Sendrr, Herro.

## Auth Pattern
- Inter-service JWT (same pattern as Budgets): Addy/Projjo/Sendrr/Herro mint short-lived JWTs with org/user claims; Digitax validates and syncs/upserts.
- Env: `DIGITAX_JWT_SECRET` (shared across services), `DIGITAX_ALLOWED_ISS=addy,projjo,sendrr,herro`.

## Data Model (core tables)
- `businesses`: map Penda business to Digitax business (TPIN, branch, api_key, env).
- `tax_items`: map products/services to Digitax items with sync status.
- `stock_movements`: track stock adjustments/sales for ZRA stock compliance.
- `tax_invoices`: smart invoices + ZRA fields + queue/sync status.
- `invoice_line_items`: lines for invoices.
- `sync_logs`: audit of sync attempts.
- `webhook_events`: inbound Digitax webhooks.

## API (v1, REST)
- Businesses: CRUD + `/sync-to-digitax`
- Items: CRUD + `/sync`, `/bulk-sync`
- Stock: `/stock/adjust`, `/stock/movements` (list/create), `/stock/balance/{itemId}`
- Invoices: CRUD (create/list/show), `/status`, `/qr-code`, `/retry`, `/bulk`
- Notes: `/invoices/{id}/credit-note`, `/debit-note`
- Reports: `/reports/sales-summary`, `/reports/sync-status`, `/reports/failed-transactions`
- Webhooks: `/api/webhooks/digitax`

## Queues & Jobs
- `SyncItemToDigitax`, `SyncStockMovement`, `ProcessInvoice`, `CheckInvoiceStatus`
- Redis queue; failed jobs table; retries.

## Env (service)
```
DIGITAX_API_URL=https://api.digitax.tech/v1
DIGITAX_SANDBOX_URL=https://sandbox-api.digitax.tech/v1
DIGITAX_DEFAULT_API_KEY=
DIGITAX_JWT_SECRET=
DIGITAX_ALLOWED_ISS=addy,projjo,sendrr,herro
WEBHOOK_SECRET=
QUEUE_CONNECTION=redis
QUEUE_FAILED_DRIVER=database
```

## Integration (Addy/Projjo/etc.)
- Addy issues Digitax JWT via the microservice token pattern.
- Digitax service middleware syncs org/user from claims (parent_app, parent_id).
- Add a Digitax client in each app for invoking `/api/v1/...`.

## Rollout
- Phase 1: scaffold service (Laravel API), migrations, auth middleware, Digitax API client stub.
- Phase 2: implement items/stock queues + webhooks.
- Phase 3: invoice processing, QR generation, polling.
- Phase 4: integrate apps; add monitoring & alerting.
