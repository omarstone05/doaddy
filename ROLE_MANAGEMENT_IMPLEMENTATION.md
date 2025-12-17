# Role Management System Implementation

## Overview
A comprehensive role management system has been implemented to allow users to be assigned various roles within organizations. The system supports both system roles (pre-defined) and custom roles that can be created by administrators.

## Components Created

### 1. OrganizationRole Model (`app/Models/OrganizationRole.php`)
- Stores role definitions with permissions
- Supports system roles (cannot be deleted) and custom roles
- Includes level-based hierarchy (higher level = more permissions)
- Methods for permission checking and role management

### 2. Database Migrations

#### `create_organization_roles_table.php`
Creates the `organization_roles` table with:
- `name` - Display name of the role
- `slug` - Unique identifier (e.g., 'owner', 'admin', 'member')
- `description` - Role description
- `permissions` - JSON array of permissions
- `level` - Numeric level (0-100, higher = more permissions)
- `is_system` - Boolean flag for system roles

#### `add_role_id_to_organization_user_table.php`
- Adds `role_id` foreign key to `organization_user` pivot table
- Migrates existing string-based roles to role_ids
- Maintains backward compatibility with `role` string field

### 3. OrganizationRoleSeeder (`database/seeders/OrganizationRoleSeeder.php`)
Creates default system roles:

1. **Owner** (Level 100)
   - Full access to all features
   - Can manage billing, delete organization, manage all users

2. **Admin** (Level 80)
   - Can manage most features
   - Cannot delete organization or manage billing

3. **Manager** (Level 60)
   - Can manage day-to-day operations
   - Limited user management

4. **Member** (Level 40)
   - Basic access to create and view content
   - Limited permissions

5. **Viewer** (Level 20)
   - Read-only access
   - Cannot create or modify content

### 4. Updated Models

#### User Model
Added methods:
- `getOrganizationRole($organizationId)` - Get OrganizationRole object
- `assignRoleInOrganization($organizationId, $roleSlug)` - Assign role to user
- `hasPermissionInOrganization($organizationId, $permission)` - Check permission

#### Organization Model
Added methods:
- `roles()` - Get all available roles
- `assignRoleToUser($user, $roleSlug)` - Assign role to user
- `changeUserRole($user, $roleSlug)` - Change user's role

### 5. Controllers

#### OrganizationRoleController (`app/Http/Controllers/OrganizationRoleController.php`)
Full CRUD operations for roles:
- `index()` - List all roles
- `create()` - Show create form
- `store()` - Create new custom role
- `show()` - View role details
- `edit()` - Show edit form (custom roles only)
- `update()` - Update role (custom roles only)
- `destroy()` - Delete role (custom roles only, if not in use)

#### AdminUserController
Added method:
- `changeOrganizationRole()` - Change user's role in an organization

### 6. Routes
Added routes in `routes/web.php`:
- `GET /admin/roles` - List roles
- `GET /admin/roles/create` - Create role form
- `POST /admin/roles` - Store new role
- `GET /admin/roles/{role}` - View role
- `GET /admin/roles/{role}/edit` - Edit role form
- `PUT /admin/roles/{role}` - Update role
- `DELETE /admin/roles/{role}` - Delete role
- `POST /admin/users/{user}/change-organization-role` - Change user role

## Usage Examples

### Assign Role to User

```php
use App\Models\User;
use App\Models\Organization;

$user = User::find($userId);
$organization = Organization::find($orgId);

// Assign role
$organization->assignRoleToUser($user, 'admin');

// Or using User model
$user->assignRoleInOrganization($orgId, 'manager');
```

### Check User Permissions

```php
// Check if user has permission
if ($user->hasPermissionInOrganization($orgId, 'invoices.create')) {
    // User can create invoices
}

// Get user's role
$role = $user->getOrganizationRole($orgId);
if ($role && $role->hasPermission('invoices.delete')) {
    // User can delete invoices
}
```

### Create Custom Role

```php
use App\Models\OrganizationRole;

$role = OrganizationRole::create([
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
        'invoices.update',
        'reports.view',
        'reports.export',
    ],
]);
```

### Change User Role via API

```http
POST /admin/users/{user}/change-organization-role
Content-Type: application/json

{
    "organization_id": "uuid-here",
    "role_slug": "admin"
}
```

## Permission Structure

Permissions follow a dot-notation pattern:
- `{resource}.{action}`

Examples:
- `organization.view`
- `organization.update`
- `users.invite`
- `users.remove`
- `invoices.create`
- `invoices.delete`
- `reports.export`

## Setup Instructions

1. **Run migrations:**
   ```bash
   php artisan migrate
   ```

2. **Seed default roles:**
   ```bash
   php artisan db:seed --class=OrganizationRoleSeeder
   ```

   Or add to `DatabaseSeeder.php`:
   ```php
   $this->call([
       OrganizationRoleSeeder::class,
   ]);
   ```

3. **Migrate existing users:**
   The migration automatically migrates existing string-based roles to role_ids. Ensure the seeder runs before the migration that adds role_id.

## Features

- ✅ System roles (Owner, Admin, Manager, Member, Viewer)
- ✅ Custom role creation
- ✅ Permission-based access control
- ✅ Level-based role hierarchy
- ✅ Backward compatibility with string roles
- ✅ Role assignment via admin panel
- ✅ Protection for system roles (cannot be deleted)
- ✅ Validation (cannot delete roles in use)

## Security Considerations

- System roles cannot be edited or deleted
- Roles in use cannot be deleted
- Role changes are logged in AdminActivityLog
- Permission checks are enforced at the model level
- Foreign key constraints prevent orphaned role assignments

## Future Enhancements

Potential additions:
- Role templates
- Permission inheritance
- Role-based UI customization
- Bulk role assignment
- Role approval workflows
- Time-based role assignments
- Role-based notifications

