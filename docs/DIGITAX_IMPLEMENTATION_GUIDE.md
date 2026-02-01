# DigiTax Zambia API Integration

A comprehensive implementation document (developer handoff)

This document describes how to integrate a taxpayer system (your app) with DigiTax Zambia API to transact with the ZRA Smart Invoice system. It is based on the official DigiTax Zambia documentation pages:
- https://zm.docs.digitax.tech/docs/getting-started
- https://zm.docs.digitax.tech/reference/get_info

---

## 1. What you are building

### 1.1 Goal

Integrate your platform with DigiTax so you can:
- Register products and services (items)
- Manage stock for stockable items
- Submit sales (invoices) to ZRA Smart Invoice via DigiTax
- Submit credit notes and debit notes
- Retrieve sale status and receipt artifacts (QR URL, signatures, tax summaries)
- Optionally ingest notices, purchases, suppliers, customers, imports (depending on your scope)

### 1.2 Core idea you must design around

DigiTax uses a queue to process transactions asynchronously. That means:
- You do not always get a final Smart Invoice receipt immediately after posting a sale
- You must support a second step to confirm completion (polling sale details or receiving a callback)

---

## 2. Environments and onboarding

### 2.1 Sandbox (TEST business)

Use DigiTax dashboard to:
1. Sign up
2. Create your profile and select country
3. Create a business using a correctly formatted TPIN and mark it as TEST
4. Create an API key in the Integrations tab
5. Store the key securely (treat it as a secret)

### 2.2 Live (LIVE business)

To go live:
1. Complete the commercial onboarding steps from DigiTax
2. Create another business using the real TPIN and mark it as LIVE
3. Generate a LIVE API key in Integrations
4. Copy and store it immediately because the dashboard may not show it again

### 2.3 Secrets and config rules

- Separate keys for TEST vs LIVE
- Never hardcode keys into frontend code
- Prefer server-side integration
- Store keys encrypted at rest
- Rotate keys using the DigiTax dashboard and update your secrets store

---

## 3. Authentication

### 3.1 Header

All API requests must include:
- `X-API-Key: <your_api_key>`

### 3.2 Security requirements

Implement:
- Central secret storage (Vault, AWS Secrets Manager, etc)
- Request logging that redacts the API key
- Per environment base URL and key isolation

---

## 4. API base URL and resources

### 4.1 Base URL

From DigiTax API examples:
- `https://api.digitax.tech/zm/v1`

### 4.2 Minimum viable integration endpoints (confirmed patterns)

These are typical endpoints used in the Smart Invoice flow:

**Business info:**
- `GET /info`

**Sales:**
- `POST /sales`
- `GET /sales`

**Credit notes:**
- `POST /credit-notes`

**Debit notes:**
- `POST /debit-notes`

Other resources likely exist in the API reference sidebar (items, stock adjustment, customers, suppliers, purchases, imports, notices). Confirm their exact paths in the API reference UI and add them to your client.

---

## 5. Data modeling (what you need to store)

Design your internal database so you can track:
- Your own invoice identifiers
- DigiTax identifiers
- The queue status
- Receipt artifacts required for printing and QR

### 5.1 Tables and fields (recommended)

#### 5.1.1 `digitax_integration_settings`

Stores per business keys and config.

| Field | Type | Notes |
|------|------|------|
| id | uuid | |
| environment | enum(TEST, LIVE) | |
| digitax_business_id | string | If DigiTax exposes one |
| tpin | string | Your business TPIN |
| api_key_ref | string | Reference to secret store |
| base_url | string | Usually `https://api.digitax.tech/zm/v1` |
| created_at | datetime | |
| updated_at | datetime | |

#### 5.1.2 `items`

Map your product catalog to DigiTax items.

| Field | Type | Notes |
|------|------|------|
| id | uuid | Your internal item id |
| name | string | |
| sku | string | Optional |
| digitax_item_id | string | Returned by DigiTax |
| item_class_code | string | Required by DigiTax |
| item_type_code | string | 1 raw material, 2 finished product, 3 service |
| origin_nation_code | string | Usually country code |
| package_unit_code | string | Controlled vocabulary |
| quantity_unit_code | string | Controlled vocabulary |
| tax_type_code | string | Controlled vocabulary |
| default_unit_price | number | |
| is_stockable | boolean | Used by your logic |
| digitax_sync_status | enum(PENDING, SYNCED, FAILED) | |
| last_digitax_error | json | For debugging |
| created_at | datetime | |
| updated_at | datetime | |

#### 5.1.3 `stock_movements`

Only needed if you manage stock.

| Field | Type | Notes |
|------|------|------|
| id | uuid | |
| item_id | uuid | FK to items |
| digitax_stock_tx_id | string | If returned |
| movement_type | enum(IN, OUT, ADJUST) | |
| quantity | number | |
| reference | string | Optional |
| digitax_sync_status | enum(PENDING, SYNCED, FAILED) | |
| created_at | datetime | |

#### 5.1.4 `sales`

Represents invoices and their queue state.

| Field | Type | Notes |
|------|------|------|
| id | uuid | Internal sale id |
| invoice_number | number | DigiTax expects numeric invoice number |
| trader_invoice_number | string | DigiTax expects string |
| receipt_type_code | enum(S, R, D) | S sale, R credit note, D debit note |
| invoice_status_code | string | Controlled vocabulary |
| payment_type_code | string | Optional |
| original_invoice_number | string | Required for credit notes |
| digitax_sale_id | string | Returned by DigiTax |
| queue_status | enum(queued, processing, complete, failed) | From sale details |
| receipt_url | string | Used for QR |
| receipt_number | string | From sale details |
| serial_number | string | From sale details |
| receipt_signature | string | From sale details |
| internal_data | string | From sale details |
| sales_tax_summary | json | From sale details |
| digitax_last_response | json | Store latest payload |
| digitax_last_error | json | Store latest error |
| created_at | datetime | |
| updated_at | datetime | |

#### 5.1.5 `sale_lines`

Invoice line items.

| Field | Type | Notes |
|------|------|------|
| id | uuid | |
| sale_id | uuid | FK |
| item_id | uuid | FK |
| digitax_item_id | string | Snapshot at time of sale |
| quantity | number | |
| unit_price | number | |
| line_total | number | |
| tax_type_code | string | Optional snapshot |
| created_at | datetime | |

#### 5.1.6 `digitax_webhook_events`

Audit log for callbacks.

| Field | Type | Notes |
|------|------|------|
| id | uuid | |
| received_at | datetime | |
| event_type | string | If provided |
| reference_id | string | sale id or item id |
| payload | json | Full raw payload |
| processed | boolean | |
| processed_at | datetime | |
| error | json | |

---

## 6. Smart Invoice flow (happy path)

DigiTax describes this required sequence for Smart Invoice usage:

### 6.1 Sequence overview

1. Create item
2. Add stock (if stockable)
3. Create sale
4. Get sale details until queue is complete

### 6.2 Detailed step-by-step

#### Step 1: Create item

- Validate required controlled codes locally before calling DigiTax
- Persist `digitax_item_id` returned by DigiTax

Example request shape:

```json
{
  "item_class_code": "string",
  "item_type_code": "1",
  "item_name": "string",
  "origin_nation_code": "ZM",
  "package_unit_code": "NET",
  "quantity_unit_code": "BA",
  "tax_type_code": "D",
  "default_unit_price": 3500,
  "callback_url": "https://yourdomain.com/webhooks/digitax/item"
}
```

Implementation notes:
- Store the request and response for audit and debugging
- If DigiTax sync happens asynchronously, mark digitax_sync_status = PENDING and wait for callback or follow-up fetch

#### Step 2: Add stock (stockable items only)

- If your product is stockable, you must add stock before selling
- If the API supports an adjust stock endpoint, call it and store its sync status

Example request shape (illustrative):

```json
{
  "item_id": "DIGITAX_ITEM_ID",
  "quantity": 25,
  "reference": "Initial stock load"
}
```

#### Step 3: Create sale

Call: `POST /sales`

Example request shape:

```json
{
  "trader_invoice_number": "INV-000123",
  "invoice_number": 123,
  "receipt_type_code": "S",
  "invoice_status_code": "01",
  "payment_type_code": "06",
  "items": [
    {
      "item_id": "DIGITAX_ITEM_ID",
      "quantity": 1,
      "unit_price": 3500
    }
  ]
}
```

Save:
- digitax_sale_id if returned
- The initial response payload
- Set queue_status = queued until completion is confirmed

#### Step 4: Get sale details and wait for completion

You must fetch sale details and check:
- queue_status
- receipt_url

When queue_status = complete:
- Use receipt_url to generate the QR code
- Use sales_tax_summary to print the tax breakdown
- Use receipt_number, serial_number, internal_data, receipt_signature for invoice metadata

If receipt_url is null:
- The background process is not complete yet
- Continue polling with exponential backoff, or rely on callbacks

---

## 7. Credit note and debit note flows

### 7.1 Credit note

Call: `POST /credit-notes`

Rules:
- Must reference the original sale via original_invoice_number
- Must include required sale fields plus credit note requirements

Example request shape:

```json
{
  "trader_invoice_number": "CN-0001",
  "invoice_number": 124,
  "receipt_type_code": "R",
  "invoice_status_code": "01",
  "original_invoice_number": "INV-000123",
  "items": [
    {
      "item_id": "DIGITAX_ITEM_ID",
      "quantity": 1,
      "unit_price": 3500
    }
  ]
}
```

### 7.2 Debit note

Call: `POST /debit-notes`

Example request shape:

```json
{
  "trader_invoice_number": "DN-0001",
  "invoice_number": 125,
  "receipt_type_code": "D",
  "invoice_status_code": "01",
  "items": [
    {
      "item_id": "DIGITAX_ITEM_ID",
      "quantity": 1,
      "unit_price": 3500
    }
  ]
}
```

### 7.3 Sale types and constraints (important)

DigiTax describes extra requirements for specific sale types such as:
- **Credit note:** return date and return reason
- **Debit note:** debit date and debit reason
- **LPO sale:** requires LPO number and VAT category rules, plus customer registration requirements
- **Export sale:** destination country code, payment type, exchange rate when foreign currency, VAT category rules
- **MTV sale:** VAT category rules

You should enforce these constraints in your UI and API layer before sending requests to DigiTax.

---

## 8. Queue handling strategy

### 8.1 Two valid approaches

**Approach A: Polling (simple)**

After POST sale, poll sale details:
- Initial delay: 2 seconds
- Backoff: exponential up to 30 seconds
- Max wait: 3 to 5 minutes depending on your UX

If not complete:
- Keep it in processing
- Provide a refresh option in the UI
- Optionally run a background job to finalize later

**Approach B: Callbacks (better)**

- Provide callback_url when supported (items, transactions)
- Build webhook receiver
- Update queue status when callback arrives
- Fallback to polling for resilience

### 8.2 Recommended internal state machine

For sales.queue_status:

| State | Meaning | Next |
|-------|---------|------|
| queued | accepted by DigiTax | processing or complete |
| processing | DigiTax working with Smart Invoice | complete or failed |
| complete | receipt_url available | final |
| failed | permanent failure | manual review or retry |

### 8.3 Idempotency

Even if DigiTax queues, you still must protect your system:
- Block multiple submissions for the same internal invoice unless you intentionally retry
- Use a unique constraint on trader_invoice_number per business and environment
- Maintain a retry counter and last attempt time

---

## 9. Webhooks implementation

### 9.1 Endpoint design

Create: `POST /webhooks/digitax`

Security options (choose one or combine):
- Secret in URL path: `/webhooks/digitax/<secret>`
- Secret header expected by your service: `X-Webhook-Secret`
- IP allowlist (only if DigiTax provides fixed IP ranges)

### 9.2 Webhook processing rules

- Always respond quickly with 200 once payload is accepted
- Do not do heavy processing inline
- Store payload in digitax_webhook_events
- Process asynchronously:
  - map reference id to sale or item
  - update status fields
  - store receipt artifacts if included

### 9.3 Replay safety

- Assume duplicate webhook events can happen
- Use event id or payload hash to deduplicate
- Only update records if the incoming status is newer than current

---

## 10. Error handling

### 10.1 Expected HTTP status codes

DigiTax documents common statuses such as:
- 200 OK, 201 Created
- 400 Bad Request
- 401 Unauthorized, 403 Forbidden
- 404 Not Found
- 500+ Server errors

### 10.2 Validation errors

DigiTax shows structured validation errors (Zod style) listing specific issues per field.

Implement:
- Parse the list of issues
- Convert into field-level UI errors
- Store raw error payload in digitax_last_error

### 10.3 Retry rules (recommended)

Retry only on:
- Network timeouts
- 502, 503, 504 server or gateway type errors

Do not auto-retry on:
- 400 validation errors
- 401, 403 auth errors
- 404 not found due to incorrect ids

---

## 11. Testing plan

### 11.1 Unit tests

- Request builder validation for required fields
- Controlled vocabulary validation for item and sales codes
- Error parsing for validation issue lists

### 11.2 Integration tests (TEST environment)

Minimum end-to-end suite:
1. Create item
2. Add stock (if stockable)
3. Create sale with that item
4. Poll sale details until complete
5. Assert receipt_url exists and is stored
6. Generate QR from receipt_url and render a sample invoice

### 11.3 Negative tests

- Missing required fields: verify structured validation response handling
- Invalid item id: verify 404 handling
- Invalid API key: verify auth error handling

---

## 12. Deployment and rollout checklist

### 12.1 Sandbox readiness

- [ ] TEST business created
- [ ] TEST API key stored in secrets manager
- [ ] Item creation works
- [ ] Stock adjustment works (if applicable)
- [ ] Sale creation works
- [ ] Sale completion confirmed and receipt_url generated
- [ ] Tax summary is displayed correctly

### 12.2 Production readiness

- [ ] LIVE business onboarded
- [ ] LIVE API key stored in secrets manager
- [ ] Webhook endpoint deployed and secured
- [ ] Monitoring for queue backlog and failures
- [ ] Audit logging enabled (requests, responses, webhook payloads)
- [ ] Retry policies implemented

---

## 13. Operational monitoring

Track:
- API error rate by endpoint and status code
- Average time from sale submission to complete
- Queue backlog count (sales not complete after N minutes)
- Webhook ingestion success rate
- Duplicate invoice submission attempts

Alert conditions:
- More than X percent 500 errors within 10 minutes
- Average completion time exceeds threshold
- Spike in validation errors (could indicate upstream UI bug)
- Webhook failures or high backlog of unprocessed webhook events

---

## 14. Implementation checklist (engineer task list)

**Phase 1: Foundation**
- [ ] Add secrets and environment config
- [ ] Build DigiTax API client with X-API-Key header
- [ ] Add structured logging and redaction
- [ ] Create database tables for items, sales, sale_lines, webhook_events

**Phase 2: Smart Invoice core**
- [ ] Implement item create and store digitax_item_id
- [ ] Implement stock adjustment (if needed)
- [ ] Implement sale create
- [ ] Implement sale detail fetch and completion logic
- [ ] Generate QR from receipt_url

**Phase 3: Notes and extras**
- [ ] Implement credit note create
- [ ] Implement debit note create
- [ ] Enforce sale type constraints in UI and API

**Phase 4: Reliability**
- [ ] Webhook receiver, storage, async processor
- [ ] Polling fallback job
- [ ] Retry rules

**Phase 5: Admin and ops**
- [ ] Admin screens to view DigiTax status per invoice
- [ ] Error dashboard and logs
- [ ] Monitoring and alerts

---

## 15. Appendix: Example cURL templates

### 15.1 Get business info

```bash
curl -X GET "https://api.digitax.tech/zm/v1/info" \
  -H "X-API-Key: YOUR_API_KEY"
```

### 15.2 Create sale

```bash
curl -X POST "https://api.digitax.tech/zm/v1/sales" \
  -H "Content-Type: application/json" \
  -H "X-API-Key: YOUR_API_KEY" \
  -d '{
    "trader_invoice_number": "INV-000123",
    "invoice_number": 123,
    "receipt_type_code": "S",
    "invoice_status_code": "01",
    "payment_type_code": "06",
    "items": [
      {
        "item_id": "DIGITAX_ITEM_ID",
        "quantity": 1,
        "unit_price": 3500
      }
    ]
  }'
```

### 15.3 List sales

```bash
curl -X GET "https://api.digitax.tech/zm/v1/sales" \
  -H "X-API-Key: YOUR_API_KEY"
```

---

## 16. Reference Data (from DigiTax API)

### Item Type Codes

| Code | Name |
|------|------|
| 1 | Raw Material |
| 2 | Finished Product |
| 3 | Service (no stock) |

### Taxation Type Codes (Zambia)

| Code | Category | Rate |
|------|----------|------|
| A | Standard Rated | 16% |
| D | Exempt | No tax |
| C1 | Exports | 0% |
| C2 | Zero-rated LPO | 0% |
| C3 | Zero-rated by nature | 0% |
| B | MTV | — |
| TL | Tourism Levy | 1.50% |
| RVAT | Reverse VAT | 16% |
| E | Disbursement | — |

### Payment Type Codes

| Code | Name |
|------|------|
| 01 | CASH |
| 02 | CREDIT |
| 03 | CASH/CREDIT |
| 04 | BANK CHECK |
| 05 | DEBIT & CREDIT CARD |
| 06 | MOBILE MONEY |
| 07 | OTHER |

### Receipt Type Codes

| Code | Name |
|------|------|
| S | Sales |
| R | Credit Note |
| D | Debit Note |

### Invoice Status Codes

| Code | Name |
|------|------|
| 01 | Wait for Approval |
| 02 | Approved |
| 03 | Cancel Requested |
| 04 | Canceled |
| 05 | Credit Note Generated |
| 06 | Transferred |

### Currency Codes

ZMW, USD, ZAR, GBP, CNY, EUR

---

## 17. Notes and assumptions

- This document uses the confirmed endpoint patterns visible in the API reference pages and the Smart Invoice getting-started flow.
- Some endpoint paths (items, stock adjustment, sale details) may need to be copied exactly from the API reference sidebar in your environment, then added to the client.
- Implement both polling and callbacks if you want the best UX and reliability.

---

**End of document.**
