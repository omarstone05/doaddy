# Testing Role Management System

This guide covers multiple ways to test the role management system.

## Prerequisites

1. **Run migrations and seeders:**
   ```bash
   php artisan migrate
   php artisan db:seed --class=OrganizationRoleSeeder
   ```

2. **Verify roles were created:**
   ```bash
   php artisan tinker
   >>> App\Models\OrganizationRole::count()
   # Should return 5 (Owner, Admin, Manager, Member, Viewer)
   >>> App\Models\OrganizationRole::pluck('slug')
   # Should show: owner, admin, manager, member, viewer
   ```

## Method 1: Using Artisan Tinker (Quick Testing)

### Test 1: Verify Roles Exist

```bash
php artisan tinker
```

```php
// Check all roles
$roles = App\Models\OrganizationRole::all();
$roles->pluck('name', 'slug');

// Check specific role
$owner = App\Models\OrganizationRole::where('slug', 'owner')->first();
$owner->permissions; // Should show array of permissions
$owner->level; // Should be 100
```

### Test 2: Assign Role to User

```php
// Get a user and organization
$user = App\Models\User::first();
$org = App\Models\Organization::first();

// Check current role
$user->getRoleInOrganization($org->id);

// Assign admin role
$org->assignRoleToUser($user, 'admin');

// Verify role was assigned
$user->getRoleInOrganization($org->id); // Should return 'admin'
$user->getOrganizationRole($org->id)->name; // Should return 'Admin'
```

### Test 3: Test Permissions

```php
// Check if user has permission
$user->hasPermissionInOrganization($org->id, 'invoices.create'); // Should return true for admin
$user->hasPermissionInOrganization($org->id, 'organization.delete'); // Should return false for admin

// Change to owner
$org->changeUserRole($user, 'owner');
$user->hasPermissionInOrganization($org->id, 'organization.delete'); // Should return true
```

### Test 4: Create Custom Role

```php
$customRole = App\Models\OrganizationRole::create([
    'name' => 'Accountant',
    'slug' => 'accountant',
    'description' => 'Can manage financial records',
    'level' => 50,
    'is_system' => false,
    'permissions' => [
        'money.view',
        'money.create',
        'money.update',
        'invoices.view',
        'invoices.create',
        'reports.view',
    ],
]);

// Assign custom role
$org->assignRoleToUser($user, 'accountant');
$user->getRoleInOrganization($org->id); // Should return 'accountant'
```

### Test 5: Test Role Hierarchy

```php
$owner = App\Models\OrganizationRole::where('slug', 'owner')->first();
$admin = App\Models\OrganizationRole::where('slug', 'admin')->first();
$member = App\Models\OrganizationRole::where('slug', 'member')->first();

// Test level comparison
$owner->isHigherThan($admin); // Should return true
$admin->isHigherThan($member); // Should return true
$member->isHigherThan($owner); // Should return false

// Test role management
$owner->canManage($admin); // Should return true
$admin->canManage($member); // Should return true
$member->canManage($admin); // Should return false
```

## Method 2: Using PHPUnit Tests

Run the automated test suite:

```bash
php artisan test --filter RoleManagementTest
```

Or run all tests:
```bash
php artisan test
```

## Method 3: Manual HTTP Testing

### Test 1: List Roles (Admin Panel)

```bash
# Login as admin first, then:
curl -X GET http://localhost/admin/roles \
  -H "Cookie: your-session-cookie"
```

Or visit in browser:
```
http://localhost/admin/roles
```

### Test 2: Change User Role via API

```bash
# Get CSRF token first from login page
# Then:
curl -X POST http://localhost/admin/users/{user-id}/change-organization-role \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -H "Cookie: your-session-cookie" \
  -d '{
    "organization_id": "org-uuid-here",
    "role_slug": "admin"
  }'
```

### Test 3: Create Custom Role

```bash
curl -X POST http://localhost/admin/roles \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -H "Cookie: your-session-cookie" \
  -d '{
    "name": "Accountant",
    "slug": "accountant",
    "description": "Financial management role",
    "level": 50,
    "permissions": ["money.view", "money.create", "invoices.view"]
  }'
```

## Method 4: Using Browser/Postman

### Step 1: Login
1. Go to `/login`
2. Login with admin credentials
3. Copy the session cookie and CSRF token

### Step 2: Test Role Assignment
1. Go to `/admin/users/{user-id}`
2. Find the "Change Role" section
3. Select organization and role
4. Submit form
5. Verify role changed

### Step 3: Test Role Management
1. Go to `/admin/roles`
2. Click "Create Role"
3. Fill in role details
4. Submit
5. Verify role appears in list

## Method 5: Database Verification

### Check organization_user table:

```sql
SELECT 
    u.email,
    o.name as organization,
    or_roles.name as role_name,
    or_roles.slug as role_slug,
    ou.role as legacy_role,
    ou.role_id
FROM organization_user ou
JOIN users u ON ou.user_id = u.id
JOIN organizations o ON ou.organization_id = o.id
LEFT JOIN organization_roles or_roles ON ou.role_id = or_roles.id
ORDER BY u.email, o.name;
```

### Check roles table:

```sql
SELECT 
    name,
    slug,
    level,
    is_system,
    JSON_LENGTH(permissions) as permission_count
FROM organization_roles
ORDER BY level DESC;
```

## Test Scenarios Checklist

### ✅ Basic Functionality
- [ ] Roles are seeded correctly
- [ ] Can assign role to user
- [ ] Can change user role
- [ ] Role is persisted in database
- [ ] Can retrieve user's role

### ✅ Permissions
- [ ] Owner has all permissions
- [ ] Admin has limited permissions
- [ ] Member has basic permissions
- [ ] Viewer has read-only permissions
- [ ] Permission checks work correctly

### ✅ Role Management
- [ ] Can create custom role
- [ ] Can update custom role
- [ ] Cannot delete system roles
- [ ] Cannot delete roles in use
- [ ] Role hierarchy works correctly

### ✅ Edge Cases
- [ ] User without role returns null
- [ ] Invalid role slug returns false
- [ ] User not in organization returns false
- [ ] Multiple users can have same role
- [ ] User can have different roles in different orgs

### ✅ Backward Compatibility
- [ ] Legacy 'role' string field still works
- [ ] Existing users migrated correctly
- [ ] Old code using string roles still functions

## Quick Test Script

Save this as `test-roles.php` in project root:

```php
<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Organization;
use App\Models\OrganizationRole;

echo "=== Role Management Test ===\n\n";

// 1. Check roles exist
echo "1. Checking roles...\n";
$roles = OrganizationRole::all();
echo "   Found {$roles->count()} roles: " . $roles->pluck('slug')->implode(', ') . "\n\n";

// 2. Get test data
$user = User::first();
$org = Organization::first();

if (!$user || !$org) {
    echo "ERROR: Need at least one user and organization\n";
    exit(1);
}

echo "2. Testing with User: {$user->email}\n";
echo "   Organization: {$org->name}\n\n";

// 3. Assign role
echo "3. Assigning admin role...\n";
$org->assignRoleToUser($user, 'admin');
$role = $user->getOrganizationRole($org->id);
echo "   User role: {$role->name} (level {$role->level})\n\n";

// 4. Test permissions
echo "4. Testing permissions...\n";
echo "   Can create invoices: " . ($user->hasPermissionInOrganization($org->id, 'invoices.create') ? 'YES' : 'NO') . "\n";
echo "   Can delete organization: " . ($user->hasPermissionInOrganization($org->id, 'organization.delete') ? 'YES' : 'NO') . "\n\n";

// 5. Change role
echo "5. Changing to owner role...\n";
$org->changeUserRole($user, 'owner');
$role = $user->getOrganizationRole($org->id);
echo "   User role: {$role->name}\n";
echo "   Can delete organization: " . ($user->hasPermissionInOrganization($org->id, 'organization.delete') ? 'YES' : 'NO') . "\n\n";

echo "=== All tests passed! ===\n";
```

Run it:
```bash
php test-roles.php
```

## Troubleshooting

### Issue: Roles not found
**Solution:** Run the seeder:
```bash
php artisan db:seed --class=OrganizationRoleSeeder
```

### Issue: role_id is null
**Solution:** The migration should have populated it. Check:
```bash
php artisan tinker
>>> DB::table('organization_user')->whereNull('role_id')->count()
```

If count > 0, manually fix:
```php
$memberRole = App\Models\OrganizationRole::where('slug', 'member')->first();
DB::table('organization_user')->whereNull('role_id')->update(['role_id' => $memberRole->id]);
```

### Issue: Foreign key constraint error
**Solution:** Ensure organization_roles table exists and has data before running the role_id migration.

### Issue: Permission check returns false
**Solution:** Verify:
1. User has a role assigned
2. Role has the permission
3. Organization ID is correct

```php
$role = $user->getOrganizationRole($orgId);
dd($role->permissions); // Check if permission exists
```



