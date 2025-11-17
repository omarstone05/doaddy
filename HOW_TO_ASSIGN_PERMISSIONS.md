# How to Give Users Permissions

There are several ways to give a user the `documents.view` permission (or any permission):

## Method 1: Assign a Role with the Permission (Recommended)

All default roles already have `documents.view` permission:
- **Owner** - Has all document permissions
- **Admin** - Has all document permissions  
- **Manager** - Can view, create, update documents
- **Member** - Can view and create documents
- **Viewer** - Can only view documents

### Via Admin Panel (UI)

1. Login as admin
2. Navigate to `/admin/users/{user-id}`
3. Find the "Change Role" section
4. Select the organization
5. Select a role (e.g., "Viewer" for read-only, "Member" for basic access)
6. Submit the form

### Via Code/Artisan Tinker

```php
// Get user and organization
$user = App\Models\User::find('user-uuid-here');
$org = App\Models\Organization::find('org-uuid-here');

// Assign a role that has documents.view permission
$org->assignRoleToUser($user, 'viewer');  // Read-only access
// OR
$org->assignRoleToUser($user, 'member');  // Can view and create
// OR
$org->assignRoleToUser($user, 'admin');   // Full access

// Verify the permission
$user->hasPermissionInOrganization($org->id, 'documents.view'); // Should return true
```

### Via API/HTTP Request

```bash
POST /admin/users/{user-id}/change-organization-role
Content-Type: application/json

{
    "organization_id": "org-uuid-here",
    "role_slug": "viewer"  // or "member", "admin", etc.
}
```

## Method 2: Create a Custom Role with Specific Permissions

If you need a custom role with only specific permissions:

### Via Admin Panel

1. Navigate to `/admin/roles`
2. Click "Create Role"
3. Fill in:
   - Name: e.g., "Document Viewer"
   - Slug: e.g., "document-viewer"
   - Description: "Can only view documents"
   - Level: e.g., 30
   - Permissions: Select `documents.view`
4. Save the role
5. Assign this role to the user (Method 1)

### Via Code

```php
use App\Models\OrganizationRole;

// Create custom role
$role = OrganizationRole::create([
    'name' => 'Document Viewer',
    'slug' => 'document-viewer',
    'description' => 'Can only view documents',
    'level' => 30,
    'is_system' => false,
    'permissions' => [
        'documents.view',
        // Add other permissions as needed
    ],
]);

// Assign to user
$org->assignRoleToUser($user, 'document-viewer');
```

## Method 3: Check Current Role and Change It

### Check what role a user currently has:

```php
$user = App\Models\User::find('user-uuid-here');
$org = App\Models\Organization::find('org-uuid-here');

// Get current role
$currentRole = $user->getRoleInOrganization($org->id);
echo "Current role: " . $currentRole; // e.g., "member"

// Check if they have permission
$hasPermission = $user->hasPermissionInOrganization($org->id, 'documents.view');
echo "Has documents.view: " . ($hasPermission ? 'YES' : 'NO');
```

### Change their role:

```php
// Change to a role with documents.view permission
$org->changeUserRole($user, 'viewer');  // or 'member', 'admin', etc.
```

## Quick Examples

### Example 1: Give a user read-only document access

```php
$user = App\Models\User::where('email', 'user@example.com')->first();
$org = App\Models\Organization::first();

// Assign Viewer role (has documents.view permission)
$org->assignRoleToUser($user, 'viewer');
```

### Example 2: Upgrade a user to have document management

```php
$user = App\Models\User::where('email', 'user@example.com')->first();
$org = App\Models\Organization::first();

// Change to Admin role (has all document permissions)
$org->changeUserRole($user, 'admin');
```

### Example 3: Check and grant permission if missing

```php
$user = App\Models\User::where('email', 'user@example.com')->first();
$org = App\Models\Organization::first();

// Check if user has permission
if (!$user->hasPermissionInOrganization($org->id, 'documents.view')) {
    // Assign a role that has the permission
    $org->assignRoleToUser($user, 'viewer');
    echo "Permission granted!";
} else {
    echo "User already has permission.";
}
```

## Using Artisan Tinker

```bash
php artisan tinker
```

Then run:

```php
// Get user
$user = App\Models\User::where('email', 'user@example.com')->first();

// Get organization
$org = App\Models\Organization::first();

// Assign role
$org->assignRoleToUser($user, 'viewer');

// Verify
$user->hasPermissionInOrganization($org->id, 'documents.view');
// Should return: true
```

## Important Notes

1. **All default roles have `documents.view`** - So any user with a role (Owner, Admin, Manager, Member, Viewer) can view documents

2. **Users must belong to the organization** - Make sure the user is a member of the organization first

3. **Role assignment is per-organization** - A user can have different roles in different organizations

4. **System roles cannot be modified** - You can only create custom roles, not edit system roles (Owner, Admin, Manager, Member, Viewer)

5. **Permission checks are automatic** - Once a role is assigned, the permission checks in controllers will work automatically

## Troubleshooting

### User still can't view documents?

1. **Check if user has a role:**
   ```php
   $user->getRoleInOrganization($org->id);
   // Should return a role slug, not null
   ```

2. **Check if role has the permission:**
   ```php
   $role = $user->getOrganizationRole($org->id);
   $role->hasPermission('documents.view');
   // Should return true
   ```

3. **Verify user belongs to organization:**
   ```php
   $user->belongsToOrganization($org->id);
   // Should return true
   ```

4. **Check organization ID:**
   ```php
   $user->organization_id; // Make sure this matches
   ```

