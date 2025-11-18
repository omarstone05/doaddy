# User Metrics Implementation

## Overview
A comprehensive user metrics system has been implemented to track user activity, engagement, and behavior patterns across the application.

## Components Created

### 1. UserMetric Model (`app/Models/UserMetric.php`)
- Stores individual user metrics with date-based tracking
- Supports multiple metric types: `login`, `session_duration`, `page_view`, `feature_usage`, `action`
- Includes metadata field for additional context (IP address, user agent, feature names, etc.)
- Relationships: belongs to User

### 2. Database Migration (`database/migrations/2025_11_17_174106_create_user_metrics_table.php`)
- Creates `user_metrics` table with:
  - `user_id` (UUID, foreign key to users)
  - `date` (date field for daily aggregation)
  - `metric_type` (string: login, session_duration, page_view, feature_usage, action)
  - `value` (integer: count or duration in seconds)
  - `metadata` (JSON: additional context)
- Indexes for efficient querying

### 3. UserMetricsService (`app/Services/UserMetricsService.php`)
Comprehensive service for tracking and retrieving user metrics:

#### Tracking Methods:
- `track()` - Generic metric tracking
- `trackLogin()` - Track user logins with IP and user agent
- `trackSessionDuration()` - Track session duration in seconds
- `trackPageView()` - Track page views with page path
- `trackFeatureUsage()` - Track feature usage
- `trackAction()` - Track generic actions

#### Retrieval Methods:
- `getUserMetrics()` - Get raw metrics for a user
- `getAggregatedMetrics()` - Get aggregated metrics (totals, averages)
- `getDailyMetrics()` - Get daily metrics for charting
- `getUserStats()` - Get comprehensive user statistics
- `getActivityTimeline()` - Get activity timeline for a user
- `getMostUsedFeatures()` - Get most used features

### 4. TrackUserActivity Middleware (`app/Http/Middleware/TrackUserActivity.php`)
- Automatically tracks page views for authenticated users
- Updates `last_active_at` timestamp
- Excludes API routes and auth pages (login, register, logout)
- Runs on every web request

### 5. Updated Components

#### User Model
- Added `metrics()` relationship to access user metrics

#### LoginController
- Tracks login events for both email and WhatsApp logins
- Captures IP address and user agent

#### AdminUserController
- Updated `show()` method to include:
  - User statistics summary
  - Activity timeline (30 days)
  - Login chart data (30 days)

## Usage Examples

### Tracking Custom Metrics

```php
use App\Services\UserMetricsService;

$metricsService = app(UserMetricsService::class);

// Track a feature usage
$metricsService->trackFeatureUsage($user, 'invoice_creation', [
    'invoice_id' => $invoice->id,
    'amount' => $invoice->total,
]);

// Track a custom action
$metricsService->trackAction($user, 'export_data', [
    'type' => 'csv',
    'records' => 150,
]);

// Track session duration
$metricsService->trackSessionDuration($user, 3600); // 1 hour in seconds
```

### Retrieving Metrics

```php
use App\Services\UserMetricsService;

$metricsService = app(UserMetricsService::class);

// Get user statistics
$stats = $metricsService->getUserStats($user);
// Returns: total_logins, logins_last_30_days, logins_last_7_days, 
//          logins_today, total_session_duration, avg_session_duration,
//          total_page_views, total_actions, last_active_date, most_used_features

// Get daily login metrics for charting
$loginChart = $metricsService->getDailyMetrics($user, 'login', 30);
// Returns: ['labels' => [...], 'values' => [...]]

// Get activity timeline
$timeline = $metricsService->getActivityTimeline($user, 7);
// Returns: array of daily metrics grouped by date

// Get aggregated metrics
$aggregated = $metricsService->getAggregatedMetrics($user, $startDate, $endDate);
// Returns: array of metric types with totals and averages
```

## Metric Types

1. **login** - User login events
   - Metadata: `ip_address`, `user_agent`

2. **session_duration** - Session duration in seconds
   - Value: Duration in seconds

3. **page_view** - Page views
   - Metadata: `page` (path), `method`, `referer`

4. **feature_usage** - Feature usage tracking
   - Metadata: `feature` (feature name), custom fields

5. **action** - Generic action tracking
   - Metadata: `action` (action name), custom fields

## Database Schema

```sql
CREATE TABLE user_metrics (
    id BIGINT PRIMARY KEY,
    user_id UUID NOT NULL,
    date DATE NOT NULL,
    metric_type VARCHAR(255) NOT NULL,
    value INTEGER DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date_type (user_id, date, metric_type),
    INDEX idx_date (date),
    INDEX idx_metric_type (metric_type)
);
```

## Setup Instructions

1. **Run the migration:**
   ```bash
   php artisan migrate
   ```

2. **The middleware is already registered** in `bootstrap/app.php` and will automatically track page views.

3. **Login tracking is already integrated** in `LoginController`.

4. **View metrics in admin panel:**
   - Navigate to `/admin/users/{user}` to see user metrics
   - Metrics are displayed in the user detail page

## Performance Considerations

- Metrics are aggregated daily to reduce database size
- Indexes are in place for efficient querying
- Middleware tracking is lightweight and won't impact performance
- Failed metric tracking won't interrupt user requests (errors are logged)

## Future Enhancements

Potential additions:
- Real-time metrics dashboard
- Export metrics to CSV/Excel
- Automated reports via email
- Metric-based user segmentation
- Retention analysis
- Cohort analysis
- A/B testing metrics

## Notes

- Metrics are automatically tracked for all authenticated users
- Failed tracking attempts are logged but don't interrupt the application flow
- The system is designed to scale with proper indexing
- Metadata is stored as JSON for flexibility



