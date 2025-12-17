<?php

// Placeholder route map for Digitax service (to be implemented in Laravel)

// Businesses
// POST   /api/v1/businesses
// GET    /api/v1/businesses
// GET    /api/v1/businesses/{id}
// PUT    /api/v1/businesses/{id}
// DELETE /api/v1/businesses/{id}
// POST   /api/v1/businesses/{id}/sync-to-digitax

// Items
// POST   /api/v1/items
// GET    /api/v1/items
// GET    /api/v1/items/{id}
// PUT    /api/v1/items/{id}
// DELETE /api/v1/items/{id}
// POST   /api/v1/items/{id}/sync
// POST   /api/v1/items/bulk-sync

// Stock
// POST   /api/v1/stock/adjust
// POST   /api/v1/stock/movements
// GET    /api/v1/stock/movements
// GET    /api/v1/stock/balance/{itemId}

// Invoices
// POST   /api/v1/invoices
// GET    /api/v1/invoices
// GET    /api/v1/invoices/{id}
// GET    /api/v1/invoices/{id}/status
// GET    /api/v1/invoices/{id}/qr-code
// POST   /api/v1/invoices/{id}/retry
// POST   /api/v1/invoices/bulk

// Credit/Debit Notes
// POST   /api/v1/invoices/{id}/credit-note
// POST   /api/v1/invoices/{id}/debit-note

// Reports
// GET    /api/v1/reports/sales-summary
// GET    /api/v1/reports/sync-status
// GET    /api/v1/reports/failed-transactions

// Webhooks
// POST   /api/webhooks/digitax
