# Migration Instructions

## Terminal Pager Issue

If your terminal is stuck in a pager (showing "HELP -- Press RETURN for more, or q when done"), follow these steps:

### Quick Fix:
1. **Press `q`** to quit the pager
2. **Press `Ctrl+C`** if `q` doesn't work
3. If still stuck, close and reopen your terminal

### Run the Migration:

Once your terminal is working normally, run:

```bash
php artisan migrate
```

Or if you want to run it non-interactively:

```bash
php artisan migrate --no-interaction
```

### What the Migration Does:

This migration will:
- Add `is_active` boolean column to the `users` table (defaults to `true`)
- Add an index on `is_active` for better query performance
- All existing users will be set to `is_active = true` by default

### After Migration:

Once the migration completes successfully, you can:
1. Go to `/admin/users` in your application
2. See the new "Status" column with Active/Inactive badges
3. Use the toggle switches to activate/deactivate users
4. Use the filter dropdown to filter by active status

### Troubleshooting:

If you continue to have terminal issues:
1. Close the current terminal window
2. Open a new terminal/PowerShell window
3. Navigate to your project: `cd C:\Users\user\Desktop\addy\doaddy`
4. Run: `php artisan migrate`



