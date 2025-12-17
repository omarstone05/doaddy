# Subscription System with Lenco Integration

## Overview
This system allows organizations to subscribe to Addy Business plans using Lenco payment gateway.

## Setup Steps

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Seed Subscription Plans
```bash
php artisan db:seed --class=SubscriptionPlanSeeder
```

This creates three default plans:
- **Starter**: ZMW 99/month (14-day trial)
- **Professional**: ZMW 299/month (14-day trial)
- **Enterprise**: ZMW 999/month (14-day trial)

### 3. Configure Lenco Webhook
In your Lenco dashboard, set the webhook URL to:
```
https://doaddy.com/lenco/subscription-webhook
```

## API Endpoints

### View Subscription Plans
**GET** `/subscriptions`
- Shows all available subscription plans
- Displays current subscription if any

### Subscribe to a Plan
**POST** `/subscriptions/subscribe`

Request body:
```json
{
    "plan_id": "uuid"
}
```

Response:
```json
{
    "success": true,
    "authorization_url": "https://...",
    "subscription_id": "uuid"
}
```

### Cancel Subscription
**POST** `/subscriptions/cancel`

Request body (optional):
```json
{
    "reason": "Cancellation reason"
}
```

### Subscription Callback
**GET** `/subscriptions/callback?reference=xxx`
- Handles redirect after payment
- Automatically activates subscription on success

## Database Structure

### Subscription Plans
- Defines available subscription tiers
- Includes pricing, features, limits
- Can be activated/deactivated

### Subscriptions
- Tracks organization subscriptions
- Links to Lenco payment references
- Manages subscription lifecycle (pending, active, cancelled, expired)

## Subscription Lifecycle

1. **User selects plan** → Creates subscription with status "pending"
2. **Payment initialized** → Lenco payment created, reference stored
3. **User completes payment** → Webhook updates subscription to "active"
4. **Organization updated** → billing_plan and mrr fields updated
5. **Renewal** → Webhook handles recurring payments
6. **Cancellation** → Subscription marked cancelled, access until end of period

## Frontend Integration

Users can:
- View available plans
- Subscribe to a plan (redirects to Lenco)
- View current subscription
- Cancel subscription

## Webhook Events

The system handles:
- Initial payment success → Activate subscription
- Renewal payment success → Extend subscription
- Payment failure → Mark as past_due
- Subscription cancellation → Update status

## Testing

1. Create a test organization
2. Navigate to `/subscriptions`
3. Select a plan
4. Complete payment via Lenco
5. Verify subscription is activated
6. Check organization billing_plan and mrr fields
