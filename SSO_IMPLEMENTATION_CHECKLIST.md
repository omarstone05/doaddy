# SSO Implementation Checklist

## Pre-SSO Security Fixes (Do Immediately)

### ✅ High Priority Security Fixes

- [ ] **Rate Limiting on Authentication**
  ```bash
  # Add to routes/web.php
  Route::post('/login', ...)->middleware('throttle:5,1');
  Route::post('/register', ...)->middleware('throttle:3,60');
  Route::post('/login/whatsapp/send-code', ...)->middleware('throttle:3,5');
  ```

- [ ] **Encrypt Google Drive Token**
  - Update User model casts
  - Run migration to re-encrypt existing tokens

- [ ] **Enable Session Encryption**
  - Set `SESSION_ENCRYPT=true` in `.env`

- [ ] **Add Login Security Events Table**
  - Create migration for security_events
  - Log all auth attempts

- [ ] **Add Content Security Policy to Nginx**

### ✅ Medium Priority

- [ ] **Implement Account Lockout**
  - Lock after 10 failed attempts
  - 30-minute lockout period
  - Admin can unlock

- [ ] **Strengthen Password Policy**
  - Minimum 10 characters
  - Must contain: uppercase, lowercase, number, special
  - Check against common passwords list

- [ ] **Add 2FA (Before SSO)**
  - TOTP via Google Authenticator
  - Recovery codes

---

## SSO Development Phases

### Phase 1: SSO Service Foundation (Week 1-3)

#### Database Setup
- [ ] Create `penda_sso` database
- [ ] Run all SSO migrations:
  - [ ] `penda_users`
  - [ ] `penda_sessions`
  - [ ] `penda_subscriptions`
  - [ ] `penda_product_accounts`
  - [ ] `penda_oauth_clients`
  - [ ] `penda_access_tokens`
  - [ ] `penda_refresh_tokens`
  - [ ] `penda_security_events`
  - [ ] `penda_mfa_backup_codes`

#### Core Authentication
- [ ] Create PendaUser model
- [ ] Implement login controller
- [ ] Implement register controller
- [ ] Implement password reset
- [ ] Add email verification flow
- [ ] Add phone verification (WhatsApp)

#### OAuth 2.0 Server
- [ ] Install Laravel Passport or custom OAuth
- [ ] Implement Authorization Code flow
- [ ] Implement PKCE extension
- [ ] Implement token refresh
- [ ] Implement token revocation
- [ ] Create `/oauth/authorize` endpoint
- [ ] Create `/oauth/token` endpoint
- [ ] Create `/oauth/userinfo` endpoint (OIDC)

### Phase 2: Security Features (Week 4-5)

#### MFA Implementation
- [ ] Create MfaService
- [ ] TOTP implementation (Google Authenticator)
- [ ] SMS OTP (via Twilio)
- [ ] Email OTP backup
- [ ] Recovery codes (10 single-use)
- [ ] MFA enrollment flow UI
- [ ] MFA verification middleware

#### Security Hardening
- [ ] Rate limiting middleware
- [ ] Brute force protection
- [ ] Session management (view/revoke)
- [ ] Login anomaly detection
- [ ] Security event logging
- [ ] IP geolocation (optional)

### Phase 3: Addy Integration (Week 6-7)

#### Backend Changes
- [ ] Add `penda_user_id` to users table
- [ ] Create SSOController
- [ ] Implement SSO login redirect
- [ ] Implement SSO callback handler
- [ ] Token storage in session
- [ ] Token refresh middleware
- [ ] Logout sync with SSO

#### Frontend Changes
- [ ] Update Login page
  - [ ] Add "Sign in with Penda" button
  - [ ] Keep legacy login (during transition)
- [ ] Update Register page
  - [ ] Redirect to SSO registration
- [ ] Add SSO callback handling
- [ ] Update logout to SSO logout

#### API Changes
- [ ] Update Sanctum config for SSO tokens
- [ ] Add SSO token validation
- [ ] Cross-product API authentication

### Phase 4: Data Migration (Week 7-8)

#### Migration Script
- [ ] Create `MigrateUsersToSSO` command
- [ ] Email-based user matching
- [ ] Handle duplicate emails
- [ ] Create subscription records
- [ ] Link organization memberships
- [ ] Generate migration report

#### Testing
- [ ] Test login flow
- [ ] Test registration flow
- [ ] Test token refresh
- [ ] Test MFA flow
- [ ] Test session management
- [ ] Test logout cascade
- [ ] Load testing

#### Rollback Plan
- [ ] Document rollback procedure
- [ ] Keep legacy auth active for 30 days
- [ ] Database backup before migration

### Phase 5: Account Portal (Week 9)

#### Features
- [ ] Profile management
- [ ] Password change
- [ ] MFA settings
- [ ] Connected accounts (Google, etc.)
- [ ] Session management
- [ ] Login history
- [ ] Subscription overview
- [ ] Organization list (all products)

### Phase 6: Rollout (Week 10)

#### Pre-Launch
- [ ] Beta test with 50 users
- [ ] Gather feedback
- [ ] Fix critical issues
- [ ] Update documentation

#### Launch
- [ ] Send migration emails
- [ ] Enable SSO for all users
- [ ] Monitor error rates
- [ ] 24/7 support coverage

#### Post-Launch
- [ ] Deprecate legacy login (30 days)
- [ ] Remove legacy auth code
- [ ] Performance optimization

---

## Technical Specifications

### OAuth Client Configuration for Addy

```php
// .env additions
PENDA_SSO_URL=https://auth.penda.co
PENDA_CLIENT_ID=addy-client-xxxxx
PENDA_CLIENT_SECRET=secret-xxxxx
PENDA_REDIRECT_URI=https://doaddy.com/auth/sso/callback

// config/services.php
'penda' => [
    'sso_url' => env('PENDA_SSO_URL'),
    'client_id' => env('PENDA_CLIENT_ID'),
    'client_secret' => env('PENDA_CLIENT_SECRET'),
    'redirect_uri' => env('PENDA_REDIRECT_URI'),
],
```

### Token Response Format

```json
{
  "access_token": "eyJhbGc...",
  "token_type": "Bearer",
  "expires_in": 900,
  "refresh_token": "dGhpcyBpc...",
  "scope": "openid profile email addy:read addy:write",
  "id_token": "eyJhbGc..."
}
```

### UserInfo Response (OIDC)

```json
{
  "sub": "penda-user-uuid",
  "penda_id": "PENDA-12345678",
  "name": "John Doe",
  "email": "john@example.com",
  "email_verified": true,
  "phone_number": "+260973660337",
  "phone_number_verified": true,
  "picture": "https://cdn.penda.co/avatars/xxx.jpg",
  "updated_at": 1702742400
}
```

### Error Response Format

```json
{
  "error": "invalid_grant",
  "error_description": "The authorization code has expired",
  "error_uri": "https://docs.penda.co/sso/errors#invalid_grant"
}
```

---

## Infrastructure Requirements

### SSO Server
- **Domain**: auth.penda.co
- **SSL**: Required (Let's Encrypt)
- **Redis**: For rate limiting & sessions
- **Database**: PostgreSQL recommended

### Network Configuration
- [ ] Configure CORS for all product domains
- [ ] Set up SSL certificates
- [ ] Configure load balancer (if needed)
- [ ] Set up CDN for static assets

### Monitoring
- [ ] Error tracking (Sentry)
- [ ] Uptime monitoring
- [ ] Performance monitoring
- [ ] Security alerts

---

## Communication Plan

| Timeline | Action |
|----------|--------|
| T-30 days | Blog post: "Introducing Penda ID" |
| T-21 days | Email to all users: What's changing |
| T-14 days | In-app banner: SSO coming soon |
| T-7 days | Email: Step-by-step guide |
| T-3 days | Final reminder email |
| T-0 | Launch SSO, email confirmation |
| T+7 days | Follow-up: Need help? |
| T+30 days | Legacy auth disabled notice |

---

## Risk Mitigation

| Risk | Mitigation |
|------|------------|
| SSO downtime = all products down | Implement fallback auth |
| Token theft | Short expiry + rotation |
| User confusion | Clear communication + support |
| Data loss during migration | Full backup + dry run |
| Performance issues | Load testing + caching |

---

**Checklist Version**: 1.0  
**Last Updated**: December 16, 2024

