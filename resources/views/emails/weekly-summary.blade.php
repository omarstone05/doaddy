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
        .date-range {
            background: rgba(255,255,255,0.2);
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            margin-top: 12px;
            font-size: 13px;
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
        .stats-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
        }
        .stat-card.highlight {
            background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%);
            border-color: #99f6e4;
        }
        .stat-card.warning {
            background: #fef3c7;
            border-color: #fde68a;
        }
        .stat-value {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            line-height: 1.2;
        }
        .stat-card.highlight .stat-value {
            color: #0d9488;
        }
        .stat-card.warning .stat-value {
            color: #b45309;
        }
        .stat-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
            margin: 24px 0 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e5e7eb;
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .metric-row:last-child {
            border-bottom: none;
        }
        .metric-label {
            color: #4b5563;
            font-size: 14px;
        }
        .metric-value {
            font-weight: 600;
            color: #111827;
            font-size: 14px;
        }
        .metric-value.positive {
            color: #059669;
        }
        .metric-value.negative {
            color: #dc2626;
        }
        .top-customers {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            margin-top: 16px;
        }
        .top-customers-title {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
        }
        .customer-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            font-size: 13px;
        }
        .customer-name {
            color: #4b5563;
        }
        .customer-amount {
            font-weight: 500;
            color: #059669;
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
            margin: 24px 0 16px;
        }
        .no-activity {
            text-align: center;
            padding: 32px;
            color: #6b7280;
        }
        .no-activity-icon {
            font-size: 48px;
            margin-bottom: 16px;
        }
        .alert-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
        }
        .alert-box-title {
            font-weight: 600;
            color: #991b1b;
            font-size: 14px;
            margin-bottom: 4px;
        }
        .alert-box-text {
            color: #b91c1c;
            font-size: 13px;
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
            <img src="{{ config('app.url') }}/assets/logos/icon-white.png" alt="Addy">
            <h1>Weekly Summary</h1>
            <p>{{ $organization->name }}</p>
            <div class="date-range">{{ $weekStartDate }} - {{ $weekEndDate }}</div>
        </div>
        
        <div class="content">
            <p class="greeting">Hi {{ $user->name }},</p>
            
            <p class="message">
                Here's your business summary for the past week. Stay on top of your finances with these key insights.
            </p>

            @if($summary['has_activity'])
                <!-- Key Stats -->
                <div class="stats-grid">
                    <div class="stat-card highlight">
                        <div class="stat-value">{{ $currency }} {{ $summary['revenue_received'] }}</div>
                        <div class="stat-label">Revenue Received</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['invoices_paid'] }}</div>
                        <div class="stat-label">Invoices Paid</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['invoices_created'] }}</div>
                        <div class="stat-label">New Invoices</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-value">{{ $summary['new_customers'] }}</div>
                        <div class="stat-label">New Customers</div>
                    </div>
                </div>

                @if($summary['overdue_invoices'] > 0)
                <div class="alert-box">
                    <div class="alert-box-title">⚠️ Attention Needed</div>
                    <div class="alert-box-text">
                        You have {{ $summary['overdue_invoices'] }} overdue invoice(s) totaling {{ $currency }} {{ $summary['overdue_amount'] }}. 
                        Consider sending payment reminders.
                    </div>
                </div>
                @endif

                <!-- Invoice Details -->
                <div class="section-title">📊 Invoicing Activity</div>
                <div class="metric-row">
                    <span class="metric-label">Invoices Created</span>
                    <span class="metric-value">{{ $summary['invoices_created'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Invoices Sent</span>
                    <span class="metric-value">{{ $summary['invoices_sent'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Invoices Paid</span>
                    <span class="metric-value">{{ $summary['invoices_paid'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Total Invoiced</span>
                    <span class="metric-value">{{ $currency }} {{ $summary['invoiced_amount'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Overdue Invoices</span>
                    <span class="metric-value {{ $summary['overdue_invoices'] > 0 ? 'negative' : '' }}">{{ $summary['overdue_invoices'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Due in Next 7 Days</span>
                    <span class="metric-value">{{ $summary['upcoming_due_invoices'] }}</span>
                </div>

                <!-- Cash Flow -->
                <div class="section-title">💰 Cash Flow</div>
                <div class="metric-row">
                    <span class="metric-label">Revenue Received</span>
                    <span class="metric-value positive">+ {{ $currency }} {{ $summary['revenue_received'] }}</span>
                </div>
                @if($summary['bills_paid'] > 0)
                <div class="metric-row">
                    <span class="metric-label">Expenses Paid</span>
                    <span class="metric-value negative">- {{ $currency }} {{ $summary['expenses_amount'] }}</span>
                </div>
                @endif
                <div class="metric-row">
                    <span class="metric-label"><strong>Net Cash Flow</strong></span>
                    <span class="metric-value {{ $summary['net_positive'] ? 'positive' : 'negative' }}">
                        {{ $summary['net_positive'] ? '+' : '-' }} {{ $currency }} {{ $summary['net_cash_flow'] }}
                    </span>
                </div>

                <!-- Top Customers -->
                @if(count($summary['top_customers']) > 0)
                <div class="section-title">⭐ Top Paying Customers This Week</div>
                <div class="top-customers">
                    @foreach($summary['top_customers'] as $customer)
                    <div class="customer-row">
                        <span class="customer-name">{{ $customer['name'] }}</span>
                        <span class="customer-amount">{{ $currency }} {{ $customer['amount'] }}</span>
                    </div>
                    @endforeach
                </div>
                @endif

                <!-- Customer Stats -->
                <div class="section-title">👥 Customer Overview</div>
                <div class="metric-row">
                    <span class="metric-label">New Customers</span>
                    <span class="metric-value">{{ $summary['new_customers'] }}</span>
                </div>
                <div class="metric-row">
                    <span class="metric-label">Total Customers</span>
                    <span class="metric-value">{{ $summary['total_customers'] }}</span>
                </div>

            @else
                <div class="no-activity">
                    <div class="no-activity-icon">📭</div>
                    <p><strong>No activity this week</strong></p>
                    <p>Start creating invoices and managing your business to see insights here.</p>
                </div>
            @endif
            
            <div style="text-align: center;">
                <a href="{{ config('app.url') }}/dashboard" class="cta-button">View Full Dashboard</a>
            </div>
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} Addy Business. All rights reserved.</p>
            <p style="margin-top: 8px;">
                <a href="{{ config('app.url') }}">Visit Addy</a> &bull;
                <a href="{{ config('app.url') }}/settings">Manage Email Preferences</a>
            </p>
        </div>
    </div>
</body>
</html>
