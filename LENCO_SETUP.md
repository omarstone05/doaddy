# Lenco Payment Gateway Integration

## Configuration

Add the following to your `.env` file:

```env
LENCO_BASE_URL=https://api.lenco.co/access/v2
LENCO_SECRET_KEY=5940ef73f894286443e336ef9718943556a68d05411c42f1a35f8090230af3c0
LENCO_PUBLIC_KEY=5940ef73f894286443e336ef9718943556a68d05411c42f1a35f8090230af3c0
LENCO_API_NAME=Addy
```

## API Endpoints

### Initialize Payment
**POST** `/lenco/initialize`

Request body:
```json
{
    "customer_id": "uuid",
    "amount": 100.00,
    "currency": "ZMW",
    "callback_url": "https://yoursite.com/lenco/callback",
    "metadata": {}
}
```

Response:
```json
{
    "success": true,
    "authorization_url": "https://...",
    "reference": "Addy_1234567890_abc123",
    "payment_id": "uuid"
}
```

### Verify Payment
**POST** `/lenco/verify`

Request body:
```json
{
    "reference": "Addy_1234567890_abc123"
}
```

### Webhook
**POST** `/lenco/webhook`

Lenco will send webhook notifications to this endpoint. Make sure to configure the webhook URL in your Lenco dashboard.

## Usage Example

```javascript
// Initialize payment
const response = await fetch('/lenco/initialize', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    },
    body: JSON.stringify({
        customer_id: 'customer-uuid',
        amount: 100.00,
        currency: 'ZMW'
    })
});

const data = await response.json();
if (data.success) {
    // Redirect user to authorization URL
    window.location.href = data.authorization_url;
}
```

## Service Class

The `LencoService` class provides the following methods:

- `initializePayment(array $data)` - Initialize a new payment
- `verifyPayment(string $reference)` - Verify payment status
- `getPayment(string $reference)` - Get payment details
- `listTransactions(array $filters)` - List transactions
- `verifyWebhookSignature(string $signature, array $payload)` - Verify webhook signature

## Integration with Payment Model

When a payment is initialized via Lenco, a `Payment` record is created with:
- `payment_method`: 'card'
- `payment_reference`: Lenco reference number
- `notes`: 'Payment via Lenco - Pending'

The payment status is updated via webhook callbacks.
