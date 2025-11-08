# Sprint 7 Plan - Settings, Notifications & Commissions

## Overview
Sprint 7 focuses on implementing Organization Settings, Notifications System, and Commission Management to complete core business operations features.

## Goals
- Organization settings management
- Notifications system (view, mark as read)
- Commission rules configuration
- Commission earnings tracking
- Templates management

## Features to Implement

### 1. Organization Settings ✅ Priority
**Status**: Organization model exists, needs settings UI

**Tasks**:
- [ ] Settings page for organization
- [ ] Update organization name, currency, timezone
- [ ] Logo upload
- [ ] Business type and industry selection
- [ ] Tone preference settings
- [ ] General settings management

**Fields**:
- Name, Slug, Business Type, Industry
- Currency, Timezone
- Logo
- Tone Preference
- Settings (JSON)

### 2. Notifications System ✅ Priority
**Status**: Migration exists with schema

**Tasks**:
- [ ] View notifications
- [ ] Mark as read/unread
- [ ] Filter notifications
- [ ] Notification badge count
- [ ] Delete notifications

**Fields**:
- Type, Title, Message
- Action URL
- Is Read, Read At

### 3. Commission Rules ✅ Priority
**Status**: Migration exists but empty, needs schema

**Tasks**:
- [ ] Complete commission_rules migration schema
- [ ] Create CommissionRule model
- [ ] Create commission rules (percentage-based, fixed, tiered)
- [ ] Assign rules to team members or departments
- [ ] CRUD operations

**Fields**:
- Name, Description
- Rule Type (percentage, fixed, tiered)
- Rate/Amount
- Applicable To (team member, department, all)
- Active status

### 4. Commission Earnings ✅ Priority
**Status**: Migration exists but empty, needs schema

**Tasks**:
- [ ] Complete commission_earnings migration schema
- [ ] Create CommissionEarning model
- [ ] Track commission earnings per sale
- [ ] View commission history
- [ ] Calculate commissions automatically

**Fields**:
- Team Member ID
- Sale ID
- Commission Rule ID
- Amount
- Status (pending, paid)
- Paid Date

### 5. Templates Management ✅ Medium Priority
**Status**: Migration exists with schema

**Tasks**:
- [ ] View templates
- [ ] Edit templates
- [ ] Template settings
- [ ] Template activation

## Technical Implementation

### Migrations to Complete
- `commission_rules` - Add proper schema
- `commission_earnings` - Add proper schema

### Models to Create
- `Notification` - Notification management
- `CommissionRule` - Commission rule configuration
- `CommissionEarning` - Commission earnings tracking

### Controllers to Create
- `SettingsController` - Organization settings
- `NotificationController` - Notification management
- `CommissionRuleController` - Commission rules CRUD
- `CommissionEarningController` - Commission earnings viewing

### Frontend Pages to Create
- `/settings` - Organization settings
- `/notifications` - Notifications list
- `/commissions/rules` - Commission rules listing
- `/commissions/rules/create` - Create commission rule
- `/commissions/rules/{id}/edit` - Edit commission rule
- `/commissions/earnings` - Commission earnings listing
- `/templates` - Templates management

### Routes to Add
```php
// Settings
Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

// Notifications
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

// Commission Rules
Route::resource('commissions/rules', CommissionRuleController::class)->names([
    'index' => 'commissions.rules.index',
    'create' => 'commissions.rules.create',
    'store' => 'commissions.rules.store',
    'edit' => 'commissions.rules.edit',
    'update' => 'commissions.rules.update',
    'destroy' => 'commissions.rules.destroy',
]);

// Commission Earnings
Route::get('/commissions/earnings', [CommissionEarningController::class, 'index'])->name('commissions.earnings.index');
```

## Integration Points
- Notifications linked to Users and Organizations
- Commission Rules linked to Team Members/Departments
- Commission Earnings linked to Sales and Commission Rules
- Settings affect organization-wide behavior

## Success Criteria
- ✅ Update organization settings
- ✅ View and manage notifications
- ✅ Create and manage commission rules
- ✅ Track commission earnings
- ✅ All routes properly registered
- ✅ Navigation links updated

## Estimated Effort
- Settings: 2-3 hours
- Notifications: 2-3 hours
- Commission Rules: 3-4 hours
- Commission Earnings: 2-3 hours
- Templates: 2-3 hours
- **Total**: ~11-16 hours

