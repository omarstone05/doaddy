# Budgets Microservice Plan

Purpose: provide a single budgets domain service that both Addy and Projjo can call, while running on the same server as the existing apps. The service owns the budgets data and exposes a stable API; Addy and Projjo become clients.

## Goals
- Single source of truth for budgets (create/update/delete, current status, burn).
- Multi-tenant (per organization) and able to scope to projects/jobs for Projjo.
- Service-to-service friendly (API keys/JWT), no user session dependency.
- Lightweight to host alongside Addy/Projjo (same server, isolated process).

## Non-goals (v1)
- Full reporting/BI; do only core CRUD + summaries.
- Cross-service UI; each app keeps its own UI but calls this API.
- Real-time streaming; simple REST is fine, webhooks optional later.

## Tech/Runtime
- Framework: Laravel/Lumen 11 slim API (matches Addy stack, easy reuse of traits/middleware).
- Runtime: PHP-FPM served via nginx upstream; listen on port 9002 (example).
- DB: dedicated schema (PostgreSQL/MySQL) with its own migrations; no shared tables.
- Queue (optional v2): Redis-backed for webhook fan-out.

## Data Model
- `budgets` (uuid pk): `organization_id` (uuid), `owner_type` (`organization|project|department`), `owner_id` (string/uuid), `name`, `category`, `currency` (char(3)), `amount` (decimal 15,2), `period` (`monthly|quarterly|yearly|custom`), `start_date`, `end_date`, `status` (`active|closed|archived`), `notes`, `created_by_app` (`addy|projjo|admin`), timestamps.
- `budget_actuals`: `id` (uuid), `budget_id` fk, `source_app`, `source_type` (e.g. `money_movement`, `project_expense`), `source_id`, `occurred_on`, `amount` (decimal), `currency`, `meta` (json), timestamps.
- `budget_adjustments`: `id`, `budget_id`, `amount_delta`, `reason`, `requested_by` (app/user id string), `approved_by`, `meta` (json), timestamps.
- `budget_snapshots` (optional cache): `budget_id`, `as_of_date`, `spent`, `remaining`, `pct_spent`.
- Derived fields (`spent`, `remaining`, `pct_spent`) are computed via aggregates on `budget_actuals` and cached.

## REST API (v1)
- `GET /health` → `{status:"ok"}`
- `POST /budgets` → create budget (body = model fields); returns budget id.
- `GET /budgets` → filter by `organization_id`, `owner_type`, `owner_id`, `period`, `status`.
- `GET /budgets/{id}` → budget with computed summary (`spent`, `remaining`, `pct_spent`).
- `PUT /budgets/{id}` → update mutable fields.
- `DELETE /budgets/{id}` → soft delete/archive.
- `POST /budgets/{id}/adjustments` → record change in allocation; returns new amount & audit trail.
- `POST /budgets/{id}/actuals` → append spend/earn actual; idempotency via `Idempotency-Key` header.
- `GET /budgets/{id}/history` → adjustments + actuals timeline.
- `GET /owners/{owner_type}/{owner_id}/summary` → aggregate budgets + burn for an owner (used by dashboards).

### Auth & tenancy
- Service-to-service API keys stored in `.env` (`BUDGETS_API_KEYS=addy:xxx,projjo:yyy`).
- Requests include `X-Service-Key` and required `X-Organization-Id`; reject if missing/invalid.
- Optional `X-Actor-Id` for auditing who initiated the change.

## Integration: Addy
- Replace BudgetLine Eloquent calls with an API client:
  - Reads: `GET /budgets` for listings; `GET /budgets/{id}` for details.
  - Writes: `POST/PUT/DELETE` budgets routed to the microservice.
  - Actuals: Money module posts expenses to `/budgets/{id}/actuals` when a transaction is approved; budget id resolved by category-to-budget mapping or explicit assignment.
- Cached summaries: store response in Redis keyed per org to minimize API chatter.

## Integration: Projjo (assumptions)
- Projects/jobs map to `owner_type=project`, `owner_id=<projjo_project_id>`.
- When project expenses are logged, Projjo posts actuals to the budget service.
- Project budget UI fetches summaries via `GET /owners/project/{project_id}/summary`.

## Deployment (same server)
- Path: `/var/www/budgets-service` (separate repo/deploy folder).
- nginx site (example):
  - Proxy `/budgets-service/` to `127.0.0.1:9002`.
  - Add `add_header X-Service budgets;` and log separately.
- Process manager: Supervisor unit `budgets-service.conf` running `php artisan serve --port=9002 --host=127.0.0.1` or php-fpm pool.
- Env: DB creds, `APP_KEY`, `BUDGETS_API_KEYS`, `LOG_CHANNEL=stack`.

## Migration/Rollout Plan
1) Stand up service + migrations; smoke test `/health`.
2) Backfill existing Addy budgets into service (one-off script reading `budget_lines` → `budgets`; Money movements → `budget_actuals`).
3) Dual-write phase in Addy: continue writing to legacy table but also POST to service; add monitoring.
4) Switch Addy reads to the service; freeze legacy table.
5) Remove legacy budget endpoints/models after stable period; run cleanup migration.
6) Onboard Projjo with API key; implement client SDK (simple PHP/Laravel + JS fetch helpers).

## Open Questions
- Currency: enforce single currency per organization or per budget?
- Category mapping: should service own categories or accept free text from callers?
- Do we need webhooks to push budget health back instead of polling?
