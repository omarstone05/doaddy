<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f3f4f6;
        }
        .email-container {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }
        .header {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: white;
            padding: 32px 24px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        .content {
            padding: 32px 24px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 16px;
        }
        .message {
            color: #4b5563;
            margin-bottom: 24px;
        }
        .org-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .org-name {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }
        .role-badge {
            display: inline-block;
            background: #0d948820;
            color: #0d9488;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 13px;
            font-weight: 500;
        }
        .added-by {
            color: #6b7280;
            font-size: 14px;
            margin-top: 12px;
        }
        .cta-button {
            display: inline-block;
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: white !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 16px 0;
        }
        .footer {
            background: #f9fafb;
            padding: 24px;
            text-align: center;
            border-top: 1px solid #e5e7eb;
        }
        .footer p {
            margin: 0;
            font-size: 12px;
            color: #6b7280;
        }
        .footer a {
            color: #0d9488;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Addy</h1>
        </div>
        
        <div class="content">
            <p class="greeting">Good news!</p>
            
            <p class="message">
                You've been added to <strong>{{ $organizationName }}</strong> on Addy by {{ $addedByName }}.
                You now have access to collaborate and help manage the business.
            </p>
            
            <div class="org-card">
                <div class="org-name">{{ $organizationName }}</div>
                <span class="role-badge">{{ $roleName }}</span>
                @if($roleDescription)
                    <p style="margin: 12px 0 0; font-size: 14px; color: #6b7280;">{{ $roleDescription }}</p>
                @endif
                <p class="added-by">Added by {{ $addedByName }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $dashboardUrl }}" class="cta-button">Go to Dashboard</a>
            </div>
            
            <p style="color: #6b7280; font-size: 14px; margin-top: 24px;">
                You can switch between organizations anytime using the organization switcher in the top navigation.
            </p>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Addy Business. All rights reserved.</p>
            <p style="margin-top: 8px;">
                <a href="{{ config('app.url') }}">Visit Addy</a> &bull;
                <a href="{{ config('app.url') }}/support">Get Help</a>
            </p>
        </div>
    </div>
</body>
</html>
