# Trust Figures – Production Readiness Report

**Date:** February 5, 2026  
**Scope:** Welcome page stats bar (hero section)

---

## Trust Figures Displayed

| Figure | Source | Display |
|--------|--------|---------|
| **Trusted since 2019** | Hardcoded | `2019` |
| **invoiced through Addy** | `Invoice::sum('total_amount')` | Formatted (e.g. K500K+, K1.2M+) |
| **businesses running on Addy** | `Organization::count()` | Integer with locale formatting |

---

## Changes Made for Production

### 1. **totalInvoiced** – Query accuracy
- **Before:** Summed all invoices including draft and cancelled.
- **After:** Excludes `draft` and `cancelled` invoices. Only counts issued invoices (sent, paid, overdue, partial).
- **Reason:** Draft and cancelled invoices were inflating the figure.

### 2. **businessCount** – Query accuracy
- **Before:** Counted all organizations.
- **After:** Excludes `suspended` organizations. Includes `active`, `trial`, and `null` status.
- **Reason:** Suspended organizations should not be counted as active businesses.

### 3. **Caching**
- **Added:** 1-hour cache (`Cache::remember('welcome_page_stats', 3600, ...)`) for homepage stats.
- **Reason:** Reduces database load on the public homepage.
- **Invalidation:** Cache refreshes automatically every hour. To force refresh: `php artisan cache:forget welcome_page_stats`

---

## Verification Checklist

- [ ] **Trusted since 2019** – Confirm Addy launched or has been trusted since 2019. If not, update the value in `Welcome.jsx` (line ~36).
- [ ] **Currency prefix** – Stats use `K` (e.g. K500K+). Confirm this matches your currency (ZMW/Kwacha).
- [ ] **Low/zero values** – If `totalInvoiced` or `businessCount` is 0, the UI shows `K0+` and `0`. This is intentional and accurate.

---

## Cache Management

```bash
# Clear homepage stats cache (e.g. after bulk data import)
php artisan cache:forget welcome_page_stats
```

---

## File Changes

- `routes/web.php` – Updated Welcome route with filtered queries and caching
