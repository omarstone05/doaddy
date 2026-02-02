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
        .header img {
            height: 48px;
            margin-bottom: 12px;
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
        .invoice-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e5e7eb;
        }
        .invoice-number {
            font-size: 18px;
            font-weight: 700;
            color: #111827;
        }
        .status-badge {
            display: inline-block;
            background: #0d948820;
            color: #0d9488;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .detail-item {
            display: flex;
            flex-direction: column;
        }
        .detail-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .detail-value {
            font-size: 15px;
            font-weight: 500;
            color: #111827;
            margin-top: 2px;
        }
        .total-row {
            margin-top: 16px;
            padding-top: 16px;
            border-top: 2px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .total-label {
            font-size: 14px;
            font-weight: 600;
            color: #6b7280;
        }
        .total-amount {
            font-size: 24px;
            font-weight: 700;
            color: #0d9488;
        }
        .custom-message {
            background: #f0fdfa;
            border-left: 4px solid #0d9488;
            padding: 16px;
            margin-bottom: 24px;
            border-radius: 0 8px 8px 0;
        }
        .custom-message-label {
            font-size: 12px;
            font-weight: 600;
            color: #0d9488;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .custom-message-text {
            color: #374151;
            font-size: 14px;
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
        .note {
            background: #fef3c7;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 13px;
            margin-top: 24px;
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
        .from-business {
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .from-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .from-name {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <img src="{{ config('app.url') }}/assets/logos/icon-white.png" alt="Addy">
            <h1>Invoice</h1>
            <p>From {{ $organizationName }}</p>
        </div>
        
        <div class="content">
            <p class="greeting">Hi {{ $customerName }},</p>
            
            <p class="message">
                Please find below the details of your invoice from <strong>{{ $organizationName }}</strong>.
            </p>

            @if($customMessage)
            <div class="custom-message">
                <div class="custom-message-label">Message from {{ $organizationName }}</div>
                <div class="custom-message-text">{{ $customMessage }}</div>
            </div>
            @endif
            
            <div class="invoice-card">
                <div class="invoice-header">
                    <span class="invoice-number">{{ $invoiceNumber }}</span>
                    <span class="status-badge">Unpaid</span>
                </div>
                
                <div class="invoice-details">
                    <div class="detail-item">
                        <span class="detail-label">Invoice Date</span>
                        <span class="detail-value">{{ $invoiceDate }}</span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">Due Date</span>
                        <span class="detail-value">{{ $dueDate }}</span>
                    </div>
                </div>
                
                <div class="total-row">
                    <span class="total-label">Total Amount</span>
                    <span class="total-amount">{{ $currency }} {{ $totalAmount }}</span>
                </div>
            </div>
            
            <div style="text-align: center;">
                <a href="{{ $viewUrl }}" class="cta-button">View Invoice</a>
            </div>
            
            <div class="note">
                <strong>Payment Terms:</strong> Please ensure payment is made by the due date to avoid any late fees. If you have any questions about this invoice, please contact {{ $organizationName }} directly.
            </div>

            <div class="from-business">
                <div class="from-label">This invoice was sent by</div>
                <div class="from-name">{{ $organizationName }}</div>
            </div>
        </div>
        
        <div class="footer">
            <p>Powered by Addy Business</p>
            <p style="margin-top: 8px;">
                <a href="{{ config('app.url') }}">Learn more about Addy</a>
            </p>
        </div>
    </div>
</body>
</html>
