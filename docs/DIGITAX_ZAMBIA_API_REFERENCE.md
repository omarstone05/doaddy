# DigiTax Zambia API – Integration Reference

**Source:** [zm.docs.digitax.tech](https://zm.docs.digitax.tech)  
**Purpose:** Map official docs to Addy SmartInvoice implementation.

---

## 1. Authentication & Errors

| Topic | Doc | Implementation |
|-------|-----|----------------|
| **Auth** | Use **X-API-Key** in header ([Start using the API](https://zm.docs.digitax.tech/docs/start-using-the-api)) | `DigitaxService::makeRequest()` sends `X-API-Key` from Integrations key |
| **Invalid key** | **403 Forbidden** with `{"message": "Unauthorized"}` ([Errors](https://zm.docs.digitax.tech/docs/errors)) | Treat 403 on any request as invalid API key |
| **HTTP codes** | 200, 201, 400, 401, 500, 501, 503 | Handle 403 for auth; 400/404 for data; 5xx for server |

---

## 2. Items API

**Docs:** [API Details](https://zm.docs.digitax.tech/docs/api-details), [Items: General Data Attributes](https://zm.docs.digitax.tech/docs/items-general-data-attributes), [Items: Item Classification](https://zm.docs.digitax.tech/docs/items-item-classification-table)

### Endpoints

| Method | Path | Description |
|--------|------|-------------|
| POST | `/items` | Create item |
| GET | `/items` | List items |
| GET | `/items/{item_id}` | Get one item |
| PUT | `/items/{item_id}` | Update item |
| DELETE | `/items/{item_id}` | Delete item |

### Item attributes (request/response)

| Attribute | Required | Type | Description / example |
|-----------|----------|------|------------------------|
| id | — | string | UUID from DigiTax, e.g. `"T9mXFPgUYn"` |
| active | PUT only | boolean | Item used/unused |
| item_class_code | Yes | string | From [Item Classification](https://zm.docs.digitax.tech/docs/items-item-classification-table), e.g. `"5059690800"` |
| item_type_code | Yes | string | 1=Raw Material, 2=Finished Product, 3=Service |
| item_name | Yes | string | e.g. `"test material item 3"` |
| origin_nation_code | Yes | string | Country code, e.g. `"ZM"` ([Country Codes](https://zm.docs.digitax.tech/docs/items-general-data-attributes#country-codes)) |
| package_unit_code | Yes | string | e.g. `"NET"` ([Packaging Unit](https://zm.docs.digitax.tech/docs/items-general-data-attributes#packaging-unit-codes)) |
| quantity_unit_code | Yes | string | e.g. `"BA"` ([Quantity Unit](https://zm.docs.digitax.tech/docs/items-general-data-attributes#quantity-unit-codes)) |
| tax_type_code | Yes | string | e.g. `"D"` ([Taxation Type](https://zm.docs.digitax.tech/docs/items-general-data-attributes#taxation-type-codes)) |
| default_unit_price | Yes | number | e.g. `3500` |
| callback_url | No | string | Webhook for item updates |

### Item Type codes

| Code | Name |
|------|------|
| 1 | Raw Material |
| 2 | Finished Product |
| 3 | Service (no stock) |

### Taxation Type (Zambia, extract)

| Code | Category | Rate |
|------|----------|------|
| A | Standard Rated | 16% |
| D | Exempt | No tax |
| C1 | Exports | 0% |
| C2 | Zero-rated LPO | 0% |
| C3 | Zero-rated by nature | 0% |
| B | MTV | — |
| TL | Tourism Levy | 1.50% |

---

## 3. Sales API

**Doc:** [Sales: Data Attributes](https://zm.docs.digitax.tech/docs/sales-data-attributes)

- **Sale:** item exchanged for currency.
- **Credit note:** reversal of a sale (same endpoint, `receipt_type_code = "R"`).
- **Debit note:** adjustment to an invoice (`receipt_type_code = "D"`).

### Attributes

| Attribute | Required | Type | Description |
|-----------|----------|------|-------------|
| sale_id (id) | GET/PUT/DELETE | string | UUID from DigiTax, e.g. `"PFHol0bfkZ"` |
| trader_invoice_number | Yes | string | Your invoice number, e.g. `"12345678901"` |
| original_invoice_number | Credit note only | string | Invoice number of the sale being reversed |
| invoice_number | Yes | number | Invoice number, e.g. `1234567890` |
| receipt_type_code | Yes | string | S=Sale, R=Credit Note, D=Debit Note |
| payment_type_code | No | string | 01–07 (see below) |
| invoice_status_code | Yes | string | 01–06 (see below) |
| items | Yes | array | Array of [Item objects](https://zm.docs.digitax.tech/docs/items-general-data-attributes) |

### Receipt Type codes

| Code | Name |
|------|------|
| S | Sales |
| R | Credit Note |
| D | Debit Note |

### Payment Type codes

| Code | Name |
|------|------|
| 01 | CASH |
| 02 | CREDIT |
| 03 | CASH/CREDIT |
| 04 | BANK CHECK |
| 05 | DEBIT & CREDIT CARD |
| 06 | MOBILE MONEY |
| 07 | OTHER |

### Invoice Status codes

| Code | Name |
|------|------|
| 01 | Wait for Approval |
| 02 | Approved |
| 03 | Cancel Requested |
| 04 | Canceled |
| 05 | Credit Note Generated |
| 06 | Transferred |

### Currency codes

ZMW, USD, ZAR, GBP, CNY, EUR.

---

## 4. Transaction status & queue

**Doc:** [Transaction status](https://zm.docs.digitax.tech/docs/transaction-status)

- Requests go through DigiTax **queue** (async, retries, callbacks, throttling).
- Use **callback_url** for item/sale status updates.
- Do not assume synchronous ZRA response; poll or use callbacks for final status.

---

## 5. Stock

**Doc:** [Stock: Data Attributes](https://zm.docs.digitax.tech/docs/stock-data-attributes)

### Stock Movement Type

| Code | Description |
|------|-------------|
| 01 | INCOMING IMPORT |
| 02 | INCOMING PURCHASE |
| 03 | INCOMING RETURN |
| 04 | INCOMING STOCK MOVEMENT |
| 05 | INCOMING PROCESSING |
| 06 | INCOMING ADJUSTMENT |
| 11 | OUTGOING SALE |
| 12 | OUTGOING RETURN |
| 13 | OUTGOING STOCK MOVEMENT |
| 14 | OUTGOING PROCESSING |
| 15 | OUTGOING DISCARDING |
| 16 | OUTGOING ADJUSTMENT |

---

## 6. Import

**Doc:** [Import: Data Attributes](https://zm.docs.digitax.tech/docs/import-data-attributes)

### Import Item Status

| Code | Description |
|------|-------------|
| 1 | UNSENT |
| 2 | WAITING |
| 3 | APPROVED |
| 4 | CANCELLED |

---

## 7. Base URL & paths

- **Auth:** `X-API-Key` header only ([Start using the API](https://zm.docs.digitax.tech/docs/start-using-the-api)).
- **Base URL:** Confirm with DigiTax (e.g. `https://api.digitax.tech` or Zambia-specific host). Addy currently uses `api.digitax.io` / `sandbox-api.digitax.io` from `DigitaxCredential::getApiUrl()`.
- **Path prefix:** Official docs do not show a global prefix; common patterns are `/api/v1` or `/v1`. Our service uses a configurable path prefix when calling Items/Sales/Stock/Import.

---

## 8. Checklist for Addy

- [x] Use **X-API-Key** only when `digitax_api_key` is set.
- [x] Treat **403** as invalid API key and show “Unauthorized”.
- [x] Use **Items** paths: POST/GET `/items`, GET/PUT/DELETE `/items/{item_id}` via `DigitaxService::listItems`, `getItem`, `createItem`, `updateItem`, `deleteItem`.
- [x] Use **Sales** path and attribute set via `DigitaxService::createSale`, `getSale`.
- [ ] Support **Stock** and **Import** when needed (add methods as required).
- [x] Support **PUT** and **DELETE** in HTTP client.
- [x] Align **testConnection** with GET `{pathPrefix}/items` and 403 = Invalid X-API-Key.
- [ ] Map **item_class_code**, **tax_type_code**, **receipt_type_code**, **payment_type_code**, **invoice_status_code** from these tables in UI/validation.
