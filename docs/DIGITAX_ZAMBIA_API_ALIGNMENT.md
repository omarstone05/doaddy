# DigiTax Zambia API – Alignment with Addy Integration

**Doc:** [Start using the DigiTax Zambia API](https://zm.docs.digitax.tech/docs/start-using-the-api)  
**Updated:** 2026-01-27

## What the official docs say

From [zm.docs.digitax.tech/docs/start-using-the-api](https://zm.docs.digitax.tech/docs/start-using-the-api):

1. **DigiTax suite**
   - DigiTax App (Mobile PWA), DigiTax Dashboard, DigiTax API.  
   - The first two are powered by the DigiTax API.

2. **Prerequisites (sandbox)**
   - Sign up on [DigiTax](https://digitax.tech/sign-up).
   - Create a profile and select country (e.g. Zambia).
   - Create a business with a sample TPIN (e.g. `2002720806`) and **set it as a TEST business**.
   - Create the **API Key under the "Integrations" tab**.

3. **Authentication**
   - Use the **X-API-Key** header when calling the API (interactive docs or your integration).
   - The API Key is the value generated under Integrations; copy and store it securely.

4. **Going LIVE**
   - Complete commercial/onboarding with DigiTax (e.g. [email protected]).
   - Create a business with your real TPIN and set it as **LIVE**.
   - Generate an API Key under Integrations for that LIVE business.
   - Use that LIVE X-API-Key for production.

5. **API keys**
   - Keys can be deactivated from Integrations (padlock → Deactivate key).

## What Addy has implemented

| Area | Implementation | Zambia alignment |
|------|----------------|------------------|
| **Auth header** | `X-API-Key` sent on every request | ✅ Matches docs |
| **API Key value** | Uses **Digitax API Key** from Integrations when set (`digitax_api_key`) | ✅ This is the key from “Integrations” |
| **Optional legacy** | If only `api_key`/`api_secret` exist, service can use HMAC (X-Timestamp, X-Signature) | For backward compat where used |
| **Zambia mode** | When `digitax_api_key` is set, only `X-API-Key` is sent (no HMAC) | ✅ Matches “use X-API-Key in header” |
| **Credentials UI** | Admin Integration → Digitax: Serial, TPIN, **Digitax API Key**, Branch ID, Environment | API Key = Integrations key; Serial/TPIN/Branch are from business setup |
| **Persistence** | `digitax_credentials`: `api_key`, `api_secret`, `digitax_api_key`, `environment`, etc. | `digitax_api_key` is the Integrations key used for X-API-Key |
| **Base URLs** | Sandbox: `https://sandbox-api.digitax.io`, Production: `https://api.digitax.io` | Confirm against Zambia docs if they use a different host (e.g. zm-specific) |

## Code-level alignment

### 1. `DigitaxService` (SmartInvoice)

- **X-API-Key source**  
  - If `digitax_api_key` is present → use it for `X-API-Key` and send **only** that header (Zambia-style).  
  - Otherwise → use `api_key` and, if `api_secret` exists, add X-Timestamp + X-Signature (legacy).

- **References**  
  - `getAuthApiKey()`: returns `digitax_api_key` when set, else `api_key`.  
  - `usesZambiaAuth()`: true when `digitax_api_key` is set.  
  - `makeRequest()`: builds headers per above.

### 2. `DigitaxCredential` model

- **Fields**
  - `digitax_api_key` → Integrations API Key (used for X-API-Key in Zambia mode).  
  - `api_key` / `api_secret` → Serial / TPIN from business setup (and legacy HMAC if needed).

### 3. Admin form (DigitaxSettings)

- **Digitax API Key**  
  - Should be the value from DigiTax Dashboard → Integrations → “Add API KEY” → copy the generated key.  
- **Serial Number / TPIN / Branch ID**  
  - Come from DigiTax business/profile setup; useful for display or future ZRA/business-specific features.  
  - For **API calls**, the official Zambia docs only require the **X-API-Key** (Integrations key).

## Checklist for Zambia

- [x] Use **X-API-Key** in the request header.
- [x] Use the **Integrations** API Key as that value (`digitax_api_key`).
- [x] Do not require HMAC for Zambia; when `digitax_api_key` is set, we send only X-API-Key.
- [ ] Confirm base URL with DigiTax if Zambia uses a different host (e.g. `https://zm.api.digitax.tech` or similar).
- [ ] Use TEST business + TEST API Key for sandbox and LIVE business + LIVE API Key for production.

## Quick test (Zambia)

1. In DigiTax: create TEST business (TPIN e.g. `2002720806`) → Integrations → create API Key.  
2. In Addy: Admin → Integration → Digitax → set **Digitax API Key** = that value, Environment = Sandbox (or Production when LIVE).  
3. Save, then use “Test connection”.  
4. Backend will send only `X-API-Key: <your_key>` to the configured base URL.

## References

- [Start using the DigiTax Zambia API](https://zm.docs.digitax.tech/docs/start-using-the-api)
- [API Details](https://zm.docs.digitax.tech/docs/api-details)
- [Items: General Data Attributes](https://zm.docs.digitax.tech/docs/items-general-data-attributes)
- [Sales: Data Attributes](https://zm.docs.digitax.tech/docs/sales-data-attributes)
- [Errors](https://zm.docs.digitax.tech/docs/errors) (403 = Invalid X-API-Key)
- **Internal:** `apps/addy/docs/DIGITAX_ZAMBIA_API_REFERENCE.md` – full reference (endpoints, attributes, codes)
- Addy: `apps/addy/app/Modules/SmartInvoice/Services/DigitaxService.php` – Items/Sales methods, testConnection via GET items, 403 handling
- Addy: `apps/addy/app/Modules/SmartInvoice/Models/DigitaxCredential.php`
- Addy: `apps/addy/resources/js/Pages/Admin/Integration/DigitaxSettings.jsx`
