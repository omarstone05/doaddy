# Google Login & Drive Options Implementation ✅

## What Was Implemented

### 1. **Google OAuth Login** 🔐
- Installed Laravel Socialite package
- Created `GoogleLoginController` for OAuth authentication
- Added Google login button to login page
- Users can now sign in with their Google account
- Automatically creates account if user doesn't exist
- Links Google account to existing user if email matches

### 2. **Per-User Google Drive Storage** 📁
- Each user can connect their own Google Drive
- Tokens stored encrypted in `users.google_drive_token`
- Users can choose between:
  - **Their own Drive** (`use_own_drive = true`)
  - **Shared Drive** (`use_own_drive = false`) - Default

### 3. **Settings Page Integration** ⚙️
- Added Google Drive connection status display
- "Connect Google Drive" button for unconnected users
- "Disconnect" button for connected users
- Toggle to switch between own Drive and shared Drive
- Shows connection date

## Database Changes

### Migration: `2025_01_27_000007_add_google_drive_token_to_users_table.php`
- `google_drive_token` (text, nullable) - Encrypted OAuth token
- `google_drive_connected_at` (datetime, nullable) - Connection timestamp

### Migration: `2025_01_27_000008_add_google_oauth_fields_to_users_table.php`
- `google_id` (string, nullable, unique) - Google OAuth ID
- `avatar` (string, nullable) - User's Google avatar URL
- `use_own_drive` (boolean, default: false) - Drive preference

## How It Works

### Google Login Flow
```
User clicks "Sign in with Google"
    ↓
Redirects to Google OAuth
    ↓
User grants permission
    ↓
Callback creates/updates user account
    ↓
User logged in → Dashboard
```

### Drive Storage Flow
```
User uploads file
    ↓
FileManager checks user preference
    ↓
use_own_drive = true → User's Google Drive
use_own_drive = false → Shared Drive (fallback)
    ↓
File saved to chosen Drive
```

## Routes Added

### Authentication (Guest)
- `GET /auth/google/login` - Redirect to Google OAuth
- `GET /auth/google/login/callback` - Handle OAuth callback

### Settings (Authenticated)
- `POST /settings/drive-preference` - Update Drive preference
- `POST /settings/disconnect-drive` - Disconnect user's Drive

## Files Modified

### Backend
- `app/Http/Controllers/Auth/GoogleLoginController.php` - New
- `app/Http/Controllers/SettingsController.php` - Added Drive methods
- `app/Services/FileManager.php` - Checks user preference
- `app/Services/GoogleDriveService.php` - Supports per-user tokens
- `app/Models/User.php` - Added fillable fields
- `config/services.php` - Added Socialite config
- `routes/web.php` - Added Google login routes

### Frontend
- `resources/js/Pages/Auth/Login.jsx` - Added Google login button
- `resources/js/Pages/Settings/Index.jsx` - Added Drive connection UI

## Configuration

### Environment Variables
```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI=https://doaddy.com/auth/google/callback
GOOGLE_DRIVE_FOLDER_ID=1zKc9rXw9kJt_AQhQf9EYfbKPk_g0vgTb
```

### Google Cloud Console Setup
1. **OAuth Consent Screen**: Add test users or publish app
2. **OAuth 2.0 Client**: Configure redirect URIs:
   - `/auth/google/callback` (for Drive connection)
   - `/auth/google/login/callback` (for login)

## User Experience

### Login Page
- Email/Password login (existing)
- WhatsApp login (existing)
- **Google login** (new) ✨

### Settings Page
- **Google Drive Section** (new):
  - Connection status indicator
  - Connect/Disconnect buttons
  - Toggle for Drive preference
  - Clear explanation of each option

## Benefits

✅ **Flexible Storage**: Users choose their preferred Drive  
✅ **Privacy**: Users can keep files in their own Drive  
✅ **Convenience**: Google login for faster access  
✅ **Backward Compatible**: Shared Drive still works as default  
✅ **Secure**: Tokens encrypted in database  

## Migration Steps

1. **Run migrations**:
   ```bash
   php artisan migrate
   ```

2. **Configure Google OAuth**:
   - Add redirect URIs in Google Cloud Console
   - Add test users or publish app

3. **Test**:
   - Try Google login on `/login`
   - Connect Drive in Settings
   - Toggle Drive preference
   - Upload a file and verify storage location

## Notes

- **Default Behavior**: Users start with `use_own_drive = false` (shared Drive)
- **Backward Compatibility**: Old shared token file still works as fallback
- **Security**: All tokens encrypted using Laravel's `Crypt` facade
- **Privacy**: Users can disconnect their Drive anytime

