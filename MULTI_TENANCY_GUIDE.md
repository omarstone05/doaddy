# Multi-Tenancy with Role Management - Complete Guide

## 🎯 System Overview

Your users can now:
- ✅ Belong to multiple businesses
- ✅ Have different roles in each business
- ✅ Switch between businesses instantly
- ✅ Login once, access everything
- ✅ Owner in one, employee in another

---

## 📊 Database Structure

### Tables Created

```
users
├── id
├── name
├── email
├── current_business_id → Which business they're currently viewing
└── ...

businesses
├── id
├── name
├── slug
├── business_type
├── email, phone, address
├── tax_number
├── currency (default: ZMW)
├── settings (JSON)
└── is_active

roles
├── id
├── name (Owner, Admin, Manager, etc.)
├── slug
├── description
├── permissions (JSON array)
└── level (100=Owner, 80=Admin, etc.)

business_user (pivot table)
├── business_id
├── user_id
├── role_id → What role user has in this business
├── is_active
├── invited_at
├── joined_at
└── UNIQUE(business_id, user_id)
```

### How It Works

```
Omar (user_id=1) has:

business_user table:
┌─────────────┬─────────┬─────────┬──────────┐
│ business_id │ user_id │ role_id │ Role     │
├─────────────┼─────────┼─────────┼──────────┤
│ 1           │ 1       │ 1       │ Owner    │ ← Penda Digital
│ 2           │ 1       │ 2       │ Admin    │ ← Client A
│ 3           │ 1       │ 5       │ Employee │ ← Client B
└─────────────┴─────────┴─────────┴──────────┘

Omar's current_business_id: 1 (viewing Penda Digital)
```

---

## 🎭 Default Roles

### 1. Owner (Level 100)
**Full control** - Can do everything
```php
Permissions:
- business.* (view, update, delete, settings)
- users.* (view, invite, remove, change_role)
- transactions.* (all financial operations)
- customers.* (full CRUD)
- products.* (full CRUD)
- reports.* (view, export)
- god_engine.access
- integrations.manage
```

### 2. Admin (Level 80)
**Almost everything** except business deletion
```php
Permissions:
- business.view, update, settings (NO delete)
- users.view, invite (NO remove, NO change_role)
- transactions.* (all operations)
- customers.*, products.*
- reports.*
- god_engine.access
```

### 3. Manager (Level 60)
**Day-to-day operations**
```php
Permissions:
- business.view
- users.view
- transactions.view, create, update
- customers.view, create, update
- products.view, create, update
- reports.view
- god_engine.access
```

### 4. Accountant (Level 50)
**Financial focus**
```php
Permissions:
- business.view
- transactions.* (full financial)
- invoices.*
- customers.view
- reports.* (full reporting)
```

### 5. Employee (Level 30)
**Basic operations**
```php
Permissions:
- business.view
- transactions.view, create
- invoices.view
- customers.view, create
- products.view
```

### 6. Viewer (Level 10)
**Read-only**
```php
Permissions:
- business.view
- transactions.view
- invoices.view
- customers.view
- products.view
- reports.view
```

---

## 🔧 How to Use

### Step 1: Install

```bash
# Copy files
cp create_multi_tenancy_structure.php database/migrations/
cp RolesSeeder.php database/seeders/
cp Business.php app/Models/
cp User.php app/Models/ (replace existing)
cp Role.php app/Models/
cp BelongsToBusiness.php app/Traits/
cp SetBusinessContext.php app/Http/Middleware/
cp BusinessController.php app/Http/Controllers/
cp BusinessSwitcher.jsx resources/js/Components/

# Run migrations
php artisan migrate

# Seed roles
php artisan db:seed --class=RolesSeeder
```

### Step 2: Register Middleware

**app/Http/Kernel.php**
```php
protected $middlewareGroups = [
    'web' => [
        // ... existing middleware
        \App\Http\Middleware\SetBusinessContext::class,
    ],
];
```

### Step 3: Add Trait to Models

For any model that belongs to a business:

**app/Models/Transaction.php**
```php
use App\Traits\BelongsToBusiness;

class Transaction extends Model
{
    use BelongsToBusiness; // Add this!
    
    // ... rest of your model
}
```

Do the same for:
- Customer
- Product
- Invoice
- Expense
- Any business-specific model

### Step 4: Add Routes

**routes/web.php**
```php
Route::middleware(['auth'])->group(function () {
    // Business management
    Route::get('/business', [BusinessController::class, 'index'])->name('business.index');
    Route::get('/business/create', [BusinessController::class, 'create'])->name('business.create');
    Route::post('/business', [BusinessController::class, 'store'])->name('business.store');
    Route::get('/business/{business}', [BusinessController::class, 'show'])->name('business.show');
    Route::put('/business/{business}', [BusinessController::class, 'update'])->name('business.update');
    Route::delete('/business/{business}', [BusinessController::class, 'destroy'])->name('business.destroy');
    
    // Business switching
    Route::post('/business/{business}/switch', [BusinessController::class, 'switch'])->name('business.switch');
    
    // Team management
    Route::post('/business/{business}/invite', [BusinessController::class, 'inviteUser'])->name('business.invite');
    Route::put('/business/{business}/users/{user}/role', [BusinessController::class, 'changeUserRole'])->name('business.change-role');
    Route::delete('/business/{business}/users/{user}', [BusinessController::class, 'removeUser'])->name('business.remove-user');
});
```

### Step 5: Add Business Switcher to Layout

**resources/js/Layouts/AuthenticatedLayout.jsx**
```jsx
import BusinessSwitcher from '@/Components/BusinessSwitcher';

export default function AuthenticatedLayout({ user, businesses, currentBusiness, children }) {
    return (
        <div>
            <nav>
                {/* Add business switcher */}
                <BusinessSwitcher
                    currentBusiness={currentBusiness}
                    businesses={businesses}
                    className="w-64"
                />
                
                {/* Rest of your nav */}
            </nav>
            
            <main>{children}</main>
        </div>
    );
}
```

---

## 💻 Code Examples

### Create a Business

```php
// User creates their first business
$user = auth()->user();

$business = $user->createBusiness([
    'name' => 'Penda Digital',
    'business_type' => 'consulting',
    'email' => 'info@penda.digital',
    'phone' => '+260977123456',
]);

// User is automatically made owner
// Business is set as current business
```

### Add User to Business

```php
$business = Business::find(1);
$newUser = User::where('email', 'employee@example.com')->first();

// Add as employee
$business->addUser($newUser, 'employee');

// Add as admin
$business->addUser($newUser, 'admin');
```

### Check Permissions

```php
$user = auth()->user();

// Check if user has permission
if ($user->can('transactions.delete')) {
    // Delete transaction
}

// Check specific role
if ($user->isOwner()) {
    // Owner-only actions
}

if ($user->hasRole('admin')) {
    // Admin actions
}

// Check in business context
$business = Business::find(1);
if ($business->userCan($user, 'users.invite')) {
    // Invite users
}
```

### Switch Business

```php
$user = auth()->user();
$business = Business::find(2);

$user->switchBusiness($business);

// Now all queries are scoped to business 2!
```

### Query Scoping (Automatic!)

```php
// This automatically only shows transactions from current business
$transactions = Transaction::all();

// Behind the scenes:
// WHERE business_id = auth()->user()->current_business_id

// To see from all businesses user has access to:
$transactions = Transaction::forUserBusinesses()->get();

// To see from specific business:
$transactions = Transaction::forBusiness($businessId)->get();
```

---

## 🎨 User Flows

### Flow 1: New User Creates Business

```
1. User signs up
2. Redirected to "Create Business" page
3. Fills in business details:
   - Name: "Penda Digital"
   - Type: "Consulting"
   - Details...
4. Submits
5. Business created
6. User automatically made Owner
7. Business set as current
8. Redirected to dashboard
```

### Flow 2: User Gets Invited

```
1. Owner invites: employee@company.com (as "Employee")
2. Email sent with invitation link
3. User clicks link:
   - If has account → Added to business
   - If no account → Sign up, then added
4. User sees "You've been added to Company X"
5. Can switch between their businesses
```

### Flow 3: User Switches Business

```
1. User clicks business switcher
2. Sees list:
   ┌─────────────────────────────────┐
   │ Current Business                │
   │ ✓ Penda Digital (Owner)        │
   │                                 │
   │ Other Businesses                │
   │   Client A (Admin)              │
   │   Client B (Employee)           │
   │                                 │
   │ [+ Create New Business]         │
   └─────────────────────────────────┘
3. Clicks "Client A"
4. Page refreshes
5. Now viewing Client A's data
6. Dashboard shows Client A's transactions
```

### Flow 4: Owner Manages Team

```
1. Owner goes to Business Settings
2. Sees team list:
   - Omar (Owner)
   - Sarah (Admin)
   - Mike (Employee)

3. Clicks "Invite User"
4. Enters email + selects role
5. User added

6. To change role:
   - Click dropdown next to user
   - Select new role
   - Saved

7. To remove:
   - Click remove button
   - Confirm
   - User removed
```

---

## 🔐 Security Features

### 1. Automatic Business Scoping

```php
// This ONLY shows current business data
$customers = Customer::all();

// Can't see other businesses' data!
```

### 2. Permission Checking

```php
// In controllers
public function destroy(Transaction $transaction)
{
    if (!auth()->user()->can('transactions.delete')) {
        abort(403);
    }
    
    $transaction->delete();
}
```

### 3. Role Hierarchy

```php
// Admin can't change Owner's role
$currentRole = auth()->user()->currentRole();
$targetRole = Role::where('slug', 'owner')->first();

if (!$currentRole->canManage($targetRole)) {
    abort(403, 'Cannot manage users with higher roles');
}
```

### 4. Owner Protection

```php
// Can't delete business with only one owner
// Must transfer ownership first

// Can't demote yourself if you're the only owner
```

---

## 📱 UI Components

### Business Switcher

Shows in top nav:
```
┌─────────────────────────┐
│ [🏢] Penda Digital     │
│      Owner          [v] │
└─────────────────────────┘
```

Click to expand:
```
┌─────────────────────────────────┐
│ Current Business                │
│ ✓ [🏢] Penda Digital           │
│        Owner • 5 members        │
│                                 │
│ Admin In                        │
│   [🏢] Client Company A        │
│        Admin • 12 members       │
│                                 │
│ Employee In                     │
│   [🏢] Client Company B        │
│        Employee • 3 members     │
│                                 │
│ [⚙️] Manage Businesses          │
│ [+] Create New Business         │
└─────────────────────────────────┘
```

### Role Badge

Next to user names:
```
Owner      → Purple badge
Admin      → Blue badge
Manager    → Green badge
Accountant → Yellow badge
Employee   → Gray badge
Viewer     → Light gray badge
```

---

## 🎯 Real-World Scenarios

### Scenario 1: Omar (Consultant)

```
Omar has 3 businesses:

1. Penda Digital (Owner)
   - His consulting company
   - Full control
   - Manages team of 5

2. Client Retail Store (Admin)
   - Client's business
   - Helping set up Addy
   - Can do most things except delete

3. Client Restaurant (Viewer)
   - Just monitoring for client
   - Read-only access
   - Can view reports
```

**Omar's workflow:**
```
Morning: Switch to Penda Digital
- Check own revenue
- Manage team
- Pay invoices

Afternoon: Switch to Client Retail Store
- Import their transactions
- Set up products
- Train their staff

Evening: Switch to Client Restaurant
- View their weekly report
- Export for client
```

### Scenario 2: Team Member Sarah

```
Sarah has 2 businesses:

1. Penda Digital (Employee)
   - Works for Omar
   - Creates transactions
   - Views customers

2. Side Business (Owner)
   - Her own small shop
   - Full control
```

**Sarah's workflow:**
```
Work hours: Penda Digital context
- Enter client transactions
- Update customer info
- Can't see financial reports (not her business)

After work: Own Business context
- Full access to everything
- Manage own finances
```

---

## 🚀 Advanced Features

### Custom Permissions

Add new permissions easily:

**RolesSeeder.php**
```php
'permissions' => [
    // ... existing
    'analytics.view',
    'exports.create',
    'api.access',
]
```

### Business Settings

Store custom settings per business:

```php
$business->update([
    'settings' => [
        'invoice_prefix' => 'INV',
        'tax_rate' => 16,
        'fiscal_year_end' => '12-31',
        'default_payment_terms' => 30,
    ],
]);

// Access
$taxRate = $business->settings['tax_rate'];
```

### Subscription Management

Track business subscriptions:

```php
$business->update([
    'subscription_ends_at' => now()->addYear(),
]);

// Check if active
if ($business->isActive()) {
    // Allow access
} else {
    // Show upgrade prompt
}
```

---

## 📊 Dashboard Integration

Update your dashboard to show business context:

```php
// DashboardController.php
public function index()
{
    $user = auth()->user();
    $business = $user->currentBusiness;
    
    $stats = [
        'business_name' => $business->name,
        'user_role' => $user->currentRole()->name,
        'revenue' => Transaction::where('type', 'income')->sum('amount'),
        'expenses' => Transaction::where('type', 'expense')->sum('amount'),
        // ... automatically scoped to current business!
    ];
    
    return Inertia::render('Dashboard', $stats);
}
```

---

## ✅ Testing Checklist

```bash
# 1. Create first business
[ ] Sign up as new user
[ ] Create business
[ ] Should be Owner
[ ] Should see dashboard

# 2. Invite user
[ ] Invite someone as Employee
[ ] They receive email
[ ] They join
[ ] They see business in switcher

# 3. Switch businesses
[ ] Click switcher
[ ] Select different business
[ ] Data changes
[ ] Dashboard shows new business data

# 4. Permissions
[ ] Employee can't delete transactions
[ ] Employee can't invite users
[ ] Admin can invite users
[ ] Owner can delete business

# 5. Business isolation
[ ] Data from Business A not visible in Business B
[ ] Switching changes visible data
[ ] Can't access business not member of
```

---

## 🎉 Summary

**What You Built:**
✅ Multi-tenancy system
✅ 6 role types with permissions
✅ Business switcher UI
✅ Automatic data scoping
✅ Team management
✅ Security & isolation

**What Users Can Do:**
✅ Create unlimited businesses
✅ Join multiple businesses
✅ Different roles per business
✅ Switch instantly
✅ Login once

**Time to Implement:** ~30 minutes
**Complexity Added:** Minimal (handled by system)
**User Experience:** Amazing! 🚀

---

Ready to go multi-tenant! 🏢

