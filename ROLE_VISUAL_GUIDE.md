# Role Visual Guide - How Roles Appear in the Site

This guide shows exactly how different roles appear throughout the application.

## 🎨 Role Color Scheme

Each role has a distinct color for easy identification:

| Role | Color | Badge Style | Usage |
|------|-------|-------------|-------|
| **Owner** | Teal | `bg-teal-100 text-teal-800 border-teal-200` | Full access, highest level |
| **Admin** | Blue | `bg-blue-100 text-blue-800 border-blue-200` | High-level management |
| **Manager** | Indigo | `bg-indigo-100 text-indigo-800 border-indigo-200` | Day-to-day operations |
| **Member** | Gray | `bg-gray-100 text-gray-800 border-gray-200` | Standard user |
| **Viewer** | Yellow | `bg-yellow-100 text-yellow-800 border-yellow-200` | Read-only access |
| **Super Admin** | Purple | `bg-purple-100 text-purple-800` | Platform admin (separate from org roles) |

## 📍 Where Roles Appear

### 1. Admin Panel - Users List (`/admin/users`)

**Location:** Main users table

**Display Format:**
```
┌─────────────────────────────────────────────────────────┐
│ User          │ Organization │ Role                     │
├─────────────────────────────────────────────────────────┤
│ John Doe      │ Acme Corp    │ [Owner]  [Admin]        │
│ john@email    │ Tech Inc     │ Acme     Tech            │
└─────────────────────────────────────────────────────────┘
```

**Visual Example:**
```
┌─────────────────────────────────────┐
│ 👤 John Doe                         │
│    john@example.com                 │
│                                     │
│ Organizations:                      │
│ • Acme Corp                         │
│ • Tech Inc                          │
│                                     │
│ Roles:                              │
│ ┌─────────┐  ┌─────────┐           │
│ │ Owner   │  │ Admin   │           │
│ │ (Teal)  │  │ (Blue)  │           │
│ └─────────┘  └─────────┘           │
│ Acme Corp    Tech Inc               │
└─────────────────────────────────────┘
```

**Code Implementation:**
- Shows up to 3 organizations with roles
- Each role badge shows: Role name + Organization name below
- Color-coded badges with borders
- Hover tooltip shows full organization name and role

### 2. Admin Panel - User Detail Page (`/admin/users/{id}`)

**Location:** "Roles & Organizations" section

**Display Format:**
```
┌─────────────────────────────────────────────────────┐
│ 👤 Roles & Organizations                            │
├─────────────────────────────────────────────────────┤
│                                                     │
│ ┌───────────────────────────────────────────────┐  │
│ │ 🏢 Acme Corp                                  │  │
│ │                                                │  │
│ │ Current Role: [Owner]                         │  │
│ │ Joined: Jan 15, 2024                          │  │
│ │                                                │  │
│ │ [Change Role ▼]                               │  │
│ └───────────────────────────────────────────────┘  │
│                                                     │
│ ┌───────────────────────────────────────────────┐  │
│ │ 🏢 Tech Inc                                    │  │
│ │                                                │  │
│ │ Current Role: [Admin]                         │  │
│ │ Joined: Feb 1, 2024                           │  │
│ │                                                │  │
│ │ [Change Role ▼]                               │  │
│ └───────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

**Features:**
- Each organization in its own card
- Large, prominent role badge
- Dropdown to change role
- Join date displayed
- Clean, organized layout

### 3. Navigation Menu - Organization Switcher

**Location:** User dropdown menu (top right)

**Display Format:**
```
┌─────────────────────────────────────┐
│ 👤 John Doe                         │
│    john@example.com                 │
├─────────────────────────────────────┤
│ Current Organization:               │
│ Acme Corp                           │
├─────────────────────────────────────┤
│ Switch Organization                 │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ Acme Corp              ✓        │ │
│ │ owner                            │ │
│ └─────────────────────────────────┘ │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ Tech Inc                         │ │
│ │ admin                            │ │
│ └─────────────────────────────────┘ │
│                                     │
│ ┌─────────────────────────────────┐ │
│ │ StartupXYZ                       │ │
│ │ member                           │ │
│ └─────────────────────────────────┘ │
└─────────────────────────────────────┘
```

**Visual Details:**
- Current organization highlighted in teal background
- Checkmark (✓) next to current organization
- Role shown in lowercase below organization name
- Click to switch between organizations
- Only shows when user has multiple organizations

### 4. User Profile/Header Areas

**Location:** Various pages showing user context

**Display Format:**
```
┌─────────────────────────────────────┐
│ 👤 John Doe                         │
│    john@example.com                 │
│    [Owner] at Acme Corp             │
└─────────────────────────────────────┘
```

## 🎯 Role Badge Examples

### Owner Badge
```
┌─────────┐
│ Owner   │  ← Teal background, teal text, teal border
└─────────┘
```

### Admin Badge
```
┌─────────┐
│ Admin   │  ← Blue background, blue text, blue border
└─────────┘
```

### Manager Badge
```
┌─────────┐
│ Manager │  ← Indigo background, indigo text, indigo border
└─────────┘
```

### Member Badge
```
┌─────────┐
│ Member  │  ← Gray background, gray text, gray border
└─────────┘
```

### Viewer Badge
```
┌─────────┐
│ Viewer  │  ← Yellow background, yellow text, yellow border
└─────────┘
```

### Super Admin Badge
```
┌──────────────┐
│ Super Admin  │  ← Purple background, purple text (no border)
└──────────────┘
```

## 📱 Responsive Display

### Desktop View
- Full role badges with organization names
- Multiple roles shown side-by-side
- Hover tooltips for additional info

### Mobile View
- Compact role badges
- Truncated organization names
- Stacked layout for multiple roles

## 🔄 Role Context Examples

### Example 1: User with Single Organization
```
User: Jane Smith
Organization: Acme Corp
Role: [Owner]
       Acme
```

### Example 2: User with Multiple Organizations
```
User: John Doe
Organizations: Acme Corp, Tech Inc, StartupXYZ
Roles: [Owner]  [Admin]  [Member]
       Acme     Tech     Startup
```

### Example 3: User with Super Admin + Organization Roles
```
User: Admin User
Organizations: Acme Corp
Roles: [Super Admin]  [Owner]
                      Acme
```

## 🎨 Visual Hierarchy

1. **Super Admin** - Purple (highest, platform-level)
2. **Owner** - Teal (organization-level, highest)
3. **Admin** - Blue (high-level management)
4. **Manager** - Indigo (operational management)
5. **Member** - Gray (standard user)
6. **Viewer** - Yellow (read-only, lowest)

## 💡 Interactive Elements

### Role Badge Hover
- Shows tooltip: "Organization Name: Role Name"
- Example: "Acme Corp: Owner"

### Role Change Dropdown
- Dropdown selector in user detail page
- Lists all available roles
- Updates immediately on selection
- Shows confirmation dialog

### Organization Switcher
- Click to switch between organizations
- Current organization highlighted
- Role shown for each organization
- Smooth transition on switch

## 📊 Table View Example

```
┌──────────────┬──────────────────┬──────────────────────────┐
│ User         │ Organization     │ Role                     │
├──────────────┼──────────────────┼──────────────────────────┤
│ John Doe     │ Acme Corp        │ ┌──────┐                 │
│              │ Tech Inc         │ │Owner │ Acme            │
│              │                  │ └──────┘                 │
│              │                  │ ┌──────┐                 │
│              │                  │ │Admin │ Tech            │
│              │                  │ └──────┘                 │
├──────────────┼──────────────────┼──────────────────────────┤
│ Jane Smith   │ Acme Corp        │ ┌──────┐                 │
│              │                  │ │Member│ Acme            │
│              │                  │ └──────┘                 │
└──────────────┴──────────────────┴──────────────────────────┘
```

## 🎯 Key Visual Features

1. **Color Coding**: Instant recognition of role level
2. **Organization Context**: Always shows which organization the role is for
3. **Consistent Styling**: Same badge style throughout the app
4. **Responsive Design**: Adapts to screen size
5. **Interactive**: Click to switch, hover for details
6. **Clear Hierarchy**: Visual order matches permission level

## 🔍 Where to See Roles

1. **Admin Panel** → `/admin/users` - See all users and their roles
2. **User Detail** → `/admin/users/{id}` - Detailed role management
3. **Navigation Menu** → Top right dropdown - Organization switcher
4. **User Profile** → Various pages - Current role display

## 📝 Notes

- Roles are always shown with organization context
- Multiple roles are displayed side-by-side
- Color coding is consistent across the application
- Super Admin is separate from organization roles
- Role badges are clickable/interactive where applicable



