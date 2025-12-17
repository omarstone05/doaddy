<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #4F46E5;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Addy Business</h1>
    </div>
    <div class="content">
        {!! $body !!}
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} Addy Business. All rights reserved.</p>
        <p>
            <a href="{{ config('app.url') }}">Visit Dashboard</a> |
            <a href="{{ config('app.url') }}/support">Get Support</a>
        </p>
    </div>
</body>
</html>

