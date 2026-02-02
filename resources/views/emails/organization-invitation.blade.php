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
        .header p {
            margin: 8px 0 0;
            opacity: 0.9;
            font-size: 14px;
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
        .invited-by {
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
        .cta-button:hover {
            opacity: 0.9;
        }
        .link-fallback {
            color: #6b7280;
            font-size: 13px;
            margin-top: 16px;
            word-break: break-all;
        }
        .link-fallback a {
            color: #0d9488;
        }
        .divider {
            border-top: 1px solid #e5e7eb;
            margin: 24px 0;
        }
        .features {
            background: #f0fdfa;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .features h3 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 600;
            color: #0d9488;
        }
        .features ul {
            margin: 0;
            padding-left: 20px;
            color: #4b5563;
            font-size: 14px;
        }
        .features li {
            margin-bottom: 6px;
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
        .expiry-note {
            background: #fef3c7;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Addy</h1>
            <p>Your AI-Powered Business Assistant</p>
        </div>
        
        <div class="content">
            <p class="greeting">
                @if($inviteeName)
                    Hi {{ $inviteeName }},
                @else
                    Hello,
                @endif
            </p>
            
            <p class="message">
                You've been invited to join <strong>{{ $organizationName }}</strong> on Addy! 
                {{ $invitedByName }} has invited you to collaborate and help manage the business.
            </p>
            
            <div class="org-card">
                <div class="org-name">{{ $organizationName }}</div>
                <span class="role-badge">{{ $roleName }}</span>
                @if($roleDescription)
                    <p style="margin: 12px 0 0; font-size: 14px; color: #6b7280;">{{ $roleDescription }}</p>
                @endif
                <p class="invited-by">Invited by {{ $invitedByName }}</p>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $acceptUrl }}" class="cta-button">Accept Invitation</a>
            </div>
            
            <p class="link-fallback">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $acceptUrl }}">{{ $acceptUrl }}</a>
            </p>
            
            <div class="divider"></div>
            
            <div class="features">
                <h3>What you'll be able to do:</h3>
                <ul>
                    <li>Access the organization's financial data and reports</li>
                    <li>Collaborate with team members</li>
                    <li>Use Addy's AI assistant for insights and tasks</li>
                    <li>Manage invoices, customers, and more</li>
                </ul>
            </div>
            
            <div class="expiry-note">
                <strong>Note:</strong> This invitation will expire in 7 days. If you didn't expect this invitation, you can safely ignore this email.
            </div>
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
