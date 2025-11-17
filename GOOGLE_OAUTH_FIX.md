# Fix Google OAuth "Access Blocked" Error

## Problem
The error "Access blocked: doaddy.com has not completed the Google verification process" occurs because the OAuth app is in **Testing** mode and your email isn't added as a test user.

## Solution: Add Test User

### Step 1: Go to Google Cloud Console
1. Visit: https://console.cloud.google.com/
2. Select project: **addy-businiess** (or your project name)

### Step 2: Navigate to Audience (OAuth Consent Screen)
From the OAuth Overview page you're currently on:

1. In the **left sidebar**, click **"Audience"** (person icon)
   - This is the OAuth consent screen settings
2. Scroll down to **"Test users"** section
3. Click **"+ ADD USERS"**
4. Add your email: **addydigital25@gmail.com**
5. Click **"ADD"**

### Step 3: Try Again
1. Visit: https://doaddy.com/auth/google
2. Should now work!

## Alternative: Publish the App (For Production)

If you want anyone to be able to authenticate (not just test users):

1. Go to **OAuth consent screen**
2. Click **PUBLISH APP**
3. Note: This may require verification for sensitive scopes
4. For `drive.file` scope, verification is usually quick

## Current Status
- **App Status**: Testing mode
- **Test User Needed**: addydigital25@gmail.com
- **Action Required**: Add test user in Google Cloud Console

---

**Quick Fix**: Add `addydigital25@gmail.com` as a test user in Google Cloud Console → OAuth consent screen → Test users.

