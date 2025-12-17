# Penda Digital SSO Migration Plan

## Executive Summary

This document outlines the security evaluation of the current Addy system and provides a comprehensive plan for migrating to a centralized Single Sign-On (SSO) system under the **Penda Digital** brand. The SSO will unify authentication across multiple products including **Addy**, **Projjo**, and future products.

---

## 📊 Current System Security Evaluation

### 1. Authentication Mechanisms

#### ✅ Strengths

| Feature | Status | Details |
|---------|--------|---------|
| Session-based Auth | ✅ Good | Database-backed sessions with proper regeneration |
| Password Hashing | ✅ Secure | Laravel's bcrypt/argon2 via model casting |
| Google OAuth | ✅ Implemented | Via Laravel Socialite |
| WhatsApp OTP | ✅ Implemented | Via Twilio with 10-min expiry |
| CSRF Protection | ✅ Active | Token validation with proper error handling |
| API Token Auth | ✅ Sanctum | Token-based API authentication |
| Remember Me | ✅ Secure | Proper token handling |

#### ⚠️ Areas for Improvement

| Issue | Risk Level | Details |
|-------|------------|---------|
| No 2FA | 🟡 Medium | Two-factor authentication not implemented |
| No Rate Limiting | 🔴 High | Login attempts not rate-limited |
| Session Encryption | 🟡 Medium | `encrypt` is false by default in session config |
| No Account Lockout | 🟡 Medium | No temporary lockout after failed attempts |
| Password Policy | 🟡 Medium | Only requires 8 chars + 1 number/special |
| No Audit Trail for Auth | 🟡 Medium | Login attempts not logged for security analysis |

### 2. Authorization & Access Control

#### ✅ Strengths

| Feature | Status | Details |
|---------|--------|---------|
| Multi-Organization | ✅ Good | Users can belong to multiple orgs |
| Role-Based Access | ✅ Implemented | Via OrganizationRole model |
| Permission System | ✅ Good | Granular permissions per role |
| Super Admin | ✅ Secure | Separate middleware protection |
| Org-Level Isolation | ✅ Good | Data scoped to organization_id |

#### ⚠️ Areas for Improvement

| Issue | Risk Level | Details |
|-------|------------|---------|
| No OAuth Scope Control | 🟡 Medium | API tokens have full access |
| No Resource Policies | 🟡 Medium | Missing Laravel Policies for some models |

### 3. Data Protection

#### ✅ Strengths

| Feature | Status | Details |
|---------|--------|---------|
| UUID Primary Keys | ✅ Secure | Non-sequential IDs prevent enumeration |
| Encrypted API Keys | ✅ Good | Platform settings encrypted at rest |
| Hidden Sensitive Fields | ✅ Good | Password, tokens hidden from JSON |
| Input Validation | ✅ Good | Laravel validation on all controllers |

#### ⚠️ Areas for Improvement

| Issue | Risk Level | Details |
|-------|------------|---------|
| Google Drive Token | 🔴 High | Token stored but not encrypted in DB |
| No Field-Level Encryption | 🟡 Medium | Sensitive user data not encrypted |

### 4. Infrastructure Security (Nginx)

#### ✅ Strengths

| Feature | Status | Details |
|---------|--------|---------|
| HTTPS Redirect | ✅ Configured | HTTP → HTTPS redirect |
| Security Headers | ✅ Good | X-Frame-Options, X-XSS-Protection, HSTS |
| PHP Version Hidden | ✅ Good | X-Powered-By removed |
| Hidden Files Blocked | ✅ Good | .* files denied |

#### ⚠️ Areas for Improvement

| Issue | Risk Level | Details |
|-------|------------|---------|
| Missing CSP | 🟡 Medium | No Content-Security-Policy header |
| Missing Permissions-Policy | 🟢 Low | No Permissions-Policy header |

---

## 🏗️ SSO Architecture Design

### System Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                      PENDA DIGITAL SSO                          │
│                    (Central Identity Provider)                   │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐          │
│  │   Penda ID   │  │  OAuth 2.0   │  │    SAML      │          │
│  │   (Native)   │  │   Provider   │  │   Provider   │          │
│  └──────────────┘  └──────────────┘  └──────────────┘          │
│                                                                  │
│  ┌──────────────────────────────────────────────────────────┐   │
│  │              UNIFIED USER PROFILE                         │   │
│  │  • Primary Email (communication)                          │   │
│  │  • Phone Number (verified)                                │   │
│  │  • Product Subscriptions[]                                │   │
│  │  • Organizations[] (across products)                      │   │
│  │  • MFA Settings                                           │   │
│  │  • Login History                                          │   │
│  └──────────────────────────────────────────────────────────┘   │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ OIDC/OAuth2/SAML
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│     ADDY     │      │    PROJJO    │      │   FUTURE     │
│   Business   │      │   Projects   │      │   PRODUCTS   │
│              │      │              │      │              │
│ doaddy.com   │      │ projjo.com   │      │  *.penda.co  │
└──────────────┘      └──────────────┘      └──────────────┘
```

### Domain Strategy

| Service | Domain | Purpose |
|---------|--------|---------|
| SSO Portal | `auth.penda.co` | Central authentication |
| User Account | `account.penda.co` | Profile & subscriptions |
| Addy | `addy.penda.co` or `doaddy.com` | Business management |
| Projjo | `projjo.penda.co` or `projjo.com` | Project management |
| API Gateway | `api.penda.co` | Centralized API |

---

## 📋 Database Schema for Penda SSO

### Central SSO Database

```sql
-- Core User Identity (SSO Level)
CREATE TABLE penda_users (
    id UUID PRIMARY KEY,
    penda_id VARCHAR(50) UNIQUE NOT NULL, -- PENDA-XXXXXXXX
    
    -- Primary Communication
    email VARCHAR(255) NOT NULL,
    email_verified_at TIMESTAMP,
    phone_number VARCHAR(20),
    phone_verified_at TIMESTAMP,
    
    -- Profile
    name VARCHAR(255) NOT NULL,
    avatar VARCHAR(500),
    timezone VARCHAR(50) DEFAULT 'Africa/Lusaka',
    locale VARCHAR(10) DEFAULT 'en',
    
    -- Security
    password VARCHAR(255),
    mfa_secret VARCHAR(100),
    mfa_enabled BOOLEAN DEFAULT FALSE,
    mfa_recovery_codes JSON,
    
    -- OAuth Identifiers
    google_id VARCHAR(100),
    microsoft_id VARCHAR(100),
    apple_id VARCHAR(100),
    
    -- Account Status
    status ENUM('active', 'suspended', 'pending_verification') DEFAULT 'active',
    suspended_at TIMESTAMP,
    suspension_reason TEXT,
    
    -- Metadata
    last_login_at TIMESTAMP,
    last_login_ip VARCHAR(45),
    login_count INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE INDEX idx_email (email),
    INDEX idx_phone (phone_number),
    INDEX idx_google (google_id),
    INDEX idx_status (status)
);

-- Product Subscriptions
CREATE TABLE penda_subscriptions (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    product_code VARCHAR(50) NOT NULL, -- 'addy', 'projjo', etc.
    plan_code VARCHAR(50) NOT NULL,
    
    -- Subscription Details
    status ENUM('active', 'trial', 'cancelled', 'expired', 'past_due'),
    billing_period ENUM('monthly', 'yearly'),
    amount DECIMAL(10,2),
    currency VARCHAR(3) DEFAULT 'ZMW',
    
    -- Dates
    trial_ends_at DATE,
    starts_at DATE,
    ends_at DATE,
    cancelled_at TIMESTAMP,
    
    -- Payment Reference
    payment_provider VARCHAR(50), -- 'lenco', 'stripe', etc.
    payment_reference VARCHAR(100),
    
    metadata JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    INDEX idx_user_product (user_id, product_code),
    INDEX idx_status (status)
);

-- Connected Product Accounts
CREATE TABLE penda_product_accounts (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    product_code VARCHAR(50) NOT NULL,
    
    -- Product-specific user ID
    product_user_id UUID NOT NULL,
    
    -- Status
    is_linked BOOLEAN DEFAULT TRUE,
    linked_at TIMESTAMP,
    unlinked_at TIMESTAMP,
    
    -- Last sync
    last_synced_at TIMESTAMP,
    sync_token VARCHAR(255),
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    UNIQUE INDEX idx_product_user (product_code, product_user_id),
    INDEX idx_user (user_id)
);

-- Authentication Sessions
CREATE TABLE penda_sessions (
    id VARCHAR(100) PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    
    -- Session Info
    ip_address VARCHAR(45),
    user_agent TEXT,
    device_type VARCHAR(20), -- 'mobile', 'desktop', 'tablet'
    device_name VARCHAR(100),
    
    -- Location (optional, from IP)
    country VARCHAR(100),
    city VARCHAR(100),
    
    -- Status
    is_current BOOLEAN DEFAULT FALSE,
    last_activity TIMESTAMP,
    expires_at TIMESTAMP,
    revoked_at TIMESTAMP,
    
    created_at TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- OAuth Clients (for products)
CREATE TABLE penda_oauth_clients (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    client_id VARCHAR(100) UNIQUE NOT NULL,
    client_secret VARCHAR(255) NOT NULL,
    
    -- Redirect URIs
    redirect_uris JSON NOT NULL,
    allowed_scopes JSON NOT NULL,
    
    -- Product Info
    product_code VARCHAR(50),
    is_first_party BOOLEAN DEFAULT FALSE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Access Tokens
CREATE TABLE penda_access_tokens (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    client_id UUID REFERENCES penda_oauth_clients(id),
    
    -- Token
    token_hash VARCHAR(100) UNIQUE NOT NULL,
    scopes JSON,
    
    -- Expiry
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP,
    
    created_at TIMESTAMP,
    
    INDEX idx_token (token_hash),
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Refresh Tokens
CREATE TABLE penda_refresh_tokens (
    id UUID PRIMARY KEY,
    access_token_id UUID REFERENCES penda_access_tokens(id),
    
    token_hash VARCHAR(100) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    revoked_at TIMESTAMP,
    
    created_at TIMESTAMP,
    
    INDEX idx_token (token_hash)
);

-- Security Events (Audit Log)
CREATE TABLE penda_security_events (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    
    event_type VARCHAR(50) NOT NULL, -- 'login', 'logout', 'password_change', 'mfa_enable', etc.
    event_status ENUM('success', 'failure') NOT NULL,
    
    -- Context
    ip_address VARCHAR(45),
    user_agent TEXT,
    product_code VARCHAR(50),
    
    -- Details
    metadata JSON,
    
    created_at TIMESTAMP,
    
    INDEX idx_user (user_id),
    INDEX idx_type (event_type),
    INDEX idx_created (created_at)
);

-- MFA Backup Codes (one-time use)
CREATE TABLE penda_mfa_backup_codes (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES penda_users(id),
    
    code_hash VARCHAR(100) NOT NULL,
    used_at TIMESTAMP,
    
    created_at TIMESTAMP,
    
    INDEX idx_user (user_id)
);
```

---

## 🔐 SSO Implementation Strategy

### Phase 1: Foundation (Weeks 1-3)

#### 1.1 Create Penda SSO Service

```
penda-sso/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   │   ├── LoginController.php
│   │   │   │   ├── RegisterController.php
│   │   │   │   ├── PasswordResetController.php
│   │   │   │   ├── MfaController.php
│   │   │   │   └── SocialAuthController.php
│   │   │   ├── OAuth/
│   │   │   │   ├── AuthorizationController.php
│   │   │   │   ├── TokenController.php
│   │   │   │   └── UserInfoController.php
│   │   │   └── Account/
│   │   │       ├── ProfileController.php
│   │   │       ├── SubscriptionController.php
│   │   │       └── SecurityController.php
│   │   └── Middleware/
│   │       ├── VerifyMfa.php
│   │       ├── RateLimitAuth.php
│   │       └── AuditAuthentication.php
│   ├── Models/
│   │   ├── PendaUser.php
│   │   ├── PendaSession.php
│   │   ├── PendaSubscription.php
│   │   ├── OAuthClient.php
│   │   └── SecurityEvent.php
│   └── Services/
│       ├── MfaService.php
│       ├── TokenService.php
│       ├── ProductSyncService.php
│       └── SecurityAuditService.php
└── config/
    ├── sso.php
    └── products.php
```

#### 1.2 OAuth 2.0 / OpenID Connect Implementation

**Supported Flows:**
- Authorization Code Flow (for web apps)
- PKCE (for mobile/SPA)
- Client Credentials (for server-to-server)

**Scopes:**
```php
// config/sso.php
return [
    'scopes' => [
        'openid' => 'Basic identity',
        'profile' => 'User profile information',
        'email' => 'Email address',
        'phone' => 'Phone number',
        'subscriptions' => 'Subscription status',
        'organizations' => 'Organization memberships',
        
        // Product-specific scopes
        'addy:read' => 'Read Addy data',
        'addy:write' => 'Write Addy data',
        'projjo:read' => 'Read Projjo data',
        'projjo:write' => 'Write Projjo data',
    ],
    
    'products' => [
        'addy' => [
            'name' => 'Addy Business',
            'base_url' => env('ADDY_URL'),
            'api_url' => env('ADDY_API_URL'),
            'default_scopes' => ['openid', 'profile', 'email', 'addy:read', 'addy:write'],
        ],
        'projjo' => [
            'name' => 'Projjo',
            'base_url' => env('PROJJO_URL'),
            'api_url' => env('PROJJO_API_URL'),
            'default_scopes' => ['openid', 'profile', 'email', 'projjo:read', 'projjo:write'],
        ],
    ],
];
```

### Phase 2: Security Enhancements (Weeks 4-5)

#### 2.1 Multi-Factor Authentication

```php
// app/Services/MfaService.php
class MfaService
{
    public function generateSecret(PendaUser $user): string;
    public function verifyCode(PendaUser $user, string $code): bool;
    public function generateRecoveryCodes(PendaUser $user): array;
    public function useRecoveryCode(PendaUser $user, string $code): bool;
    public function getQrCodeUrl(PendaUser $user): string;
}
```

**MFA Options:**
- TOTP (Google Authenticator, Authy)
- SMS OTP (via Twilio)
- Email OTP
- Recovery Codes (10 single-use codes)

#### 2.2 Rate Limiting & Security

```php
// app/Http/Middleware/RateLimitAuth.php
class RateLimitAuth
{
    protected $limits = [
        'login' => ['attempts' => 5, 'decay' => 300], // 5 attempts per 5 min
        'register' => ['attempts' => 3, 'decay' => 3600], // 3 per hour
        'password_reset' => ['attempts' => 3, 'decay' => 3600],
        'mfa_verify' => ['attempts' => 5, 'decay' => 300],
    ];
}
```

#### 2.3 Session Management

- Single-device or multi-device options
- Remote session revocation
- Session activity display
- Automatic logout on password change

### Phase 3: Migration (Weeks 6-8)

#### 3.1 Data Migration Strategy

```php
// Migration Command
class MigrateUsersToSSO extends Command
{
    public function handle()
    {
        // For each Addy user:
        // 1. Check if Penda account exists (by email)
        // 2. If not, create Penda account
        // 3. Link Addy account to Penda account
        // 4. Create subscription record
        // 5. Send migration notification email
    }
}
```

**Migration Rules:**
1. **Email Match**: Users with same email across products get merged
2. **Phone Match**: Secondary matching on verified phone numbers
3. **Manual Resolution**: Conflicts flagged for manual review
4. **Grace Period**: Old login methods work for 30 days

#### 3.2 Addy Integration Changes

**Changes to Addy:**

```php
// config/auth.php (Updated)
return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'sso' => [
            'driver' => 'penda-sso',
            'provider' => 'penda-users',
        ],
    ],
    
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'penda-users' => [
            'driver' => 'penda',
            'api_url' => env('PENDA_SSO_URL'),
        ],
    ],
];
```

**New Addy User Model:**

```php
// app/Models/User.php (Updated)
class User extends Authenticatable
{
    protected $fillable = [
        'penda_user_id',      // NEW: Link to Penda SSO
        'name',
        'email',
        'organization_id',
        // ... existing fields
    ];
    
    // New method to sync from Penda
    public function syncFromPenda(array $pendaData): void
    {
        $this->update([
            'name' => $pendaData['name'],
            'email' => $pendaData['email'],
            'avatar' => $pendaData['avatar'],
        ]);
    }
}
```

**New Authentication Flow:**

```php
// app/Http/Controllers/Auth/SSOController.php
class SSOController extends Controller
{
    public function redirect()
    {
        $state = Str::random(40);
        session(['sso_state' => $state]);
        
        $query = http_build_query([
            'client_id' => config('services.penda.client_id'),
            'redirect_uri' => route('sso.callback'),
            'response_type' => 'code',
            'scope' => 'openid profile email addy:read addy:write',
            'state' => $state,
        ]);
        
        return redirect(config('services.penda.sso_url') . '/oauth/authorize?' . $query);
    }
    
    public function callback(Request $request)
    {
        // Verify state
        if ($request->state !== session('sso_state')) {
            abort(400, 'Invalid state');
        }
        
        // Exchange code for token
        $tokenResponse = Http::post(config('services.penda.sso_url') . '/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => config('services.penda.client_id'),
            'client_secret' => config('services.penda.client_secret'),
            'redirect_uri' => route('sso.callback'),
            'code' => $request->code,
        ]);
        
        // Get user info
        $userInfo = Http::withToken($tokenResponse->json('access_token'))
            ->get(config('services.penda.sso_url') . '/oauth/userinfo')
            ->json();
        
        // Find or create local user
        $user = User::updateOrCreate(
            ['penda_user_id' => $userInfo['sub']],
            [
                'name' => $userInfo['name'],
                'email' => $userInfo['email'],
                'avatar' => $userInfo['picture'] ?? null,
            ]
        );
        
        // Login and store tokens
        Auth::login($user);
        session([
            'penda_access_token' => $tokenResponse->json('access_token'),
            'penda_refresh_token' => $tokenResponse->json('refresh_token'),
            'penda_token_expires' => now()->addSeconds($tokenResponse->json('expires_in')),
        ]);
        
        return redirect()->intended('/dashboard');
    }
}
```

### Phase 4: User Experience (Weeks 9-10)

#### 4.1 Penda Account Portal (account.penda.co)

**Features:**
- View/edit profile
- Manage subscriptions across products
- View all organizations
- Security settings (password, MFA)
- Session management
- Login history
- Notification preferences

#### 4.2 Unified Login Page (auth.penda.co)

**Features:**
- Clean, branded login page
- Product context (show which product redirected)
- Social login options
- WhatsApp OTP option
- MFA challenge page
- Password reset flow

---

## 🔄 Migration Timeline

```
Week 1-2:  SSO Database & Core Authentication
Week 3:    OAuth 2.0 Server Implementation
Week 4-5:  MFA & Security Enhancements
Week 6:    Addy SSO Integration
Week 7:    User Migration Script & Testing
Week 8:    Beta Testing with Select Users
Week 9:    Penda Account Portal
Week 10:   Public Rollout & Legacy Deprecation
Week 11-12: Projjo Integration
```

---

## 🚨 Security Recommendations

### Immediate Actions (Before SSO)

1. **Enable Rate Limiting on Login**
   ```php
   // routes/web.php
   Route::post('/login', [LoginController::class, 'store'])
       ->middleware('throttle:5,1'); // 5 attempts per minute
   ```

2. **Encrypt Google Drive Token**
   ```php
   // app/Models/User.php
   protected $casts = [
       'google_drive_token' => 'encrypted',
   ];
   ```

3. **Enable Session Encryption**
   ```php
   // config/session.php
   'encrypt' => true,
   ```

4. **Add Content Security Policy**
   ```nginx
   add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self' https://api.openai.com https://api.anthropic.com;";
   ```

5. **Add Login Audit Trail**
   ```php
   // Create security_events table migration
   ```

### SSO Security Requirements

1. **Token Security**
   - Short-lived access tokens (15 min)
   - Refresh token rotation
   - Token binding to IP/device

2. **Cross-Site Protection**
   - Strict CORS policies
   - SameSite=Strict cookies for SSO
   - PKCE required for public clients

3. **Monitoring**
   - Real-time anomaly detection
   - Geo-location alerts
   - Unusual login pattern alerts

---

## 📊 Unified User Profile Schema

```json
{
  "penda_id": "PENDA-12345678",
  "profile": {
    "name": "John Doe",
    "email": "john@company.com",
    "phone": "+260973660337",
    "avatar": "https://cdn.penda.co/avatars/...",
    "timezone": "Africa/Lusaka",
    "locale": "en"
  },
  "security": {
    "mfa_enabled": true,
    "mfa_methods": ["totp", "sms"],
    "last_password_change": "2024-12-01T00:00:00Z",
    "recovery_email": "john.backup@gmail.com"
  },
  "subscriptions": [
    {
      "product": "addy",
      "plan": "business",
      "status": "active",
      "expires_at": "2025-12-01"
    },
    {
      "product": "projjo",
      "plan": "team",
      "status": "trial",
      "trial_ends_at": "2025-01-15"
    }
  ],
  "organizations": [
    {
      "product": "addy",
      "org_id": "uuid-1",
      "name": "My Company",
      "role": "owner"
    },
    {
      "product": "projjo",
      "org_id": "uuid-2",
      "name": "My Company Projects",
      "role": "admin"
    }
  ],
  "connected_accounts": {
    "google": "google-id-xxx",
    "microsoft": null,
    "apple": null
  }
}
```

---

## 💰 Subscription Management

### Product Bundles

| Bundle | Products | Discount |
|--------|----------|----------|
| Starter | Addy Free | - |
| Business | Addy Business | - |
| Business+ | Addy + Projjo | 15% |
| Enterprise | All Products | 25% |

### Cross-Product Features

- **Single Billing**: One invoice for all products
- **Unified Payment Methods**: Pay once, access all
- **Credit System**: Unused credits transferable
- **Family/Team Plans**: Share across products

---

## 🎯 Success Metrics

| Metric | Target |
|--------|--------|
| SSO Login Success Rate | >99.5% |
| Average Login Time | <3 seconds |
| MFA Adoption | >30% in 6 months |
| Support Tickets (Auth) | -50% reduction |
| Cross-Product Usage | +20% increase |
| User Churn | -15% reduction |

---

## 📞 Support & Communication

### Migration Communication Plan

1. **T-30 days**: Announce SSO coming
2. **T-14 days**: Detailed guide sent
3. **T-7 days**: Reminder with FAQ
4. **T-0**: Migration begins
5. **T+7 days**: Follow-up support

### Help Resources

- SSO FAQ page
- Video tutorials
- Live chat support
- Migration hotline

---

**Document Version**: 1.0  
**Last Updated**: December 16, 2024  
**Author**: Security Architecture Team  
**Next Review**: January 15, 2025

