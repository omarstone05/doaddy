<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #1f2937; background-color: #f3f4f6;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background-color: #f3f4f6;">
        <tr>
            <td align="center" style="padding: 20px;">
                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="600" style="max-width: 600px; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); padding: 32px 24px; text-align: center;">
                            <img src="{{ config('app.url') }}/assets/logos/icon-white.png" alt="Addy" style="height: 48px; margin-bottom: 12px;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: white;">Weekly Summary</h1>
                            <p style="margin: 8px 0 0; opacity: 0.9; font-size: 14px; color: white;">{{ $organization->name }}</p>
                            <span style="display: inline-block; background: rgba(255,255,255,0.2); padding: 6px 16px; border-radius: 20px; margin-top: 12px; font-size: 13px; color: white;">{{ $weekStartDate }} - {{ $weekEndDate }}</span>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px 24px;">
                            <p style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Hi {{ $user->name }},</p>
                            
                            <p style="color: #4b5563; margin: 0 0 24px 0;">
                                Here's your business summary for the past week. Stay on top of your finances with these key insights.
                            </p>

                            @if($summary['has_activity'])
                                <!-- Key Stats Grid -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td width="50%" style="padding: 0 8px 16px 0;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%); border: 1px solid #99f6e4; border-radius: 12px;">
                                                <tr>
                                                    <td style="padding: 16px; text-align: center;">
                                                        <p style="font-size: 24px; font-weight: 700; color: #0d9488; margin: 0; line-height: 1.2;">{{ $currency }} {{ $summary['revenue_received'] }}</p>
                                                        <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 4px 0 0 0;">Revenue Received</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="50%" style="padding: 0 0 16px 8px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px;">
                                                <tr>
                                                    <td style="padding: 16px; text-align: center;">
                                                        <p style="font-size: 24px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;">{{ $summary['invoices_paid'] }}</p>
                                                        <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 4px 0 0 0;">Invoices Paid</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td width="50%" style="padding: 0 8px 0 0;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px;">
                                                <tr>
                                                    <td style="padding: 16px; text-align: center;">
                                                        <p style="font-size: 24px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;">{{ $summary['invoices_created'] }}</p>
                                                        <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 4px 0 0 0;">New Invoices</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td width="50%" style="padding: 0 0 0 8px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px;">
                                                <tr>
                                                    <td style="padding: 16px; text-align: center;">
                                                        <p style="font-size: 24px; font-weight: 700; color: #111827; margin: 0; line-height: 1.2;">{{ $summary['new_customers'] }}</p>
                                                        <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 4px 0 0 0;">New Customers</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                @if($summary['overdue_invoices'] > 0)
                                <!-- Alert Box -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; padding: 16px;">
                                            <p style="font-weight: 600; color: #991b1b; font-size: 14px; margin: 0 0 4px 0;">⚠️ Attention Needed</p>
                                            <p style="color: #b91c1c; font-size: 13px; margin: 0;">
                                                You have {{ $summary['overdue_invoices'] }} overdue invoice(s) totaling {{ $currency }} {{ $summary['overdue_amount'] }}. 
                                                Consider sending payment reminders.
                                            </p>
                                        </td>
                                    </tr>
                                </table>
                                @endif

                                <!-- Invoicing Activity Section -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 600; color: #111827; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                                            📊 Invoicing Activity
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Invoices Created</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['invoices_created'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Invoices Sent</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['invoices_sent'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Invoices Paid</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['invoices_paid'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Total Invoiced</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #111827; font-size: 14px;">{{ $currency }} {{ $summary['invoiced_amount'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Overdue Invoices</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; font-size: 14px; color: {{ $summary['overdue_invoices'] > 0 ? '#dc2626' : '#111827' }};">{{ $summary['overdue_invoices'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; color: #4b5563; font-size: 14px;">Due in Next 7 Days</td>
                                                    <td align="right" style="padding: 12px 0; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['upcoming_due_invoices'] }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Cash Flow Section -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 600; color: #111827; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                                            💰 Cash Flow
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Revenue Received</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #059669; font-size: 14px;">+ {{ $currency }} {{ $summary['revenue_received'] }}</td>
                                                </tr>
                                                @if($summary['bills_paid'] > 0)
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">Expenses Paid</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #dc2626; font-size: 14px;">- {{ $currency }} {{ $summary['expenses_amount'] }}</td>
                                                </tr>
                                                @endif
                                                <tr>
                                                    <td style="padding: 12px 0; color: #4b5563; font-size: 14px;"><strong>Net Cash Flow</strong></td>
                                                    <td align="right" style="padding: 12px 0; font-weight: 600; font-size: 14px; color: {{ $summary['net_positive'] ? '#059669' : '#dc2626' }};">
                                                        {{ $summary['net_positive'] ? '+' : '-' }} {{ $currency }} {{ $summary['net_cash_flow'] }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Top Customers Section -->
                                @if(count($summary['top_customers']) > 0)
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 600; color: #111827; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                                            ⭐ Top Paying Customers This Week
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding-top: 8px;">
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f9fafb; border-radius: 12px;">
                                                <tr>
                                                    <td style="padding: 16px;">
                                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                            @foreach($summary['top_customers'] as $customer)
                                                            <tr>
                                                                <td style="padding: 8px 0; color: #4b5563; font-size: 13px;">{{ $customer['name'] }}</td>
                                                                <td align="right" style="padding: 8px 0; font-weight: 500; color: #059669; font-size: 13px;">{{ $currency }} {{ $customer['amount'] }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </table>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                                @endif

                                <!-- Customer Overview Section -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                    <tr>
                                        <td style="font-size: 16px; font-weight: 600; color: #111827; padding-bottom: 12px; border-bottom: 2px solid #e5e7eb;">
                                            👥 Customer Overview
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                                <tr>
                                                    <td style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; color: #4b5563; font-size: 14px;">New Customers</td>
                                                    <td align="right" style="padding: 12px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['new_customers'] }}</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 12px 0; color: #4b5563; font-size: 14px;">Total Customers</td>
                                                    <td align="right" style="padding: 12px 0; font-weight: 600; color: #111827; font-size: 14px;">{{ $summary['total_customers'] }}</td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>

                            @else
                                <!-- No Activity -->
                                <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                    <tr>
                                        <td style="text-align: center; padding: 32px;">
                                            <p style="font-size: 48px; margin: 0 0 16px 0;">📭</p>
                                            <p style="font-weight: 600; color: #6b7280; margin: 0 0 8px 0;">No activity this week</p>
                                            <p style="color: #6b7280; margin: 0;">Start creating invoices and managing your business to see insights here.</p>
                                        </td>
                                    </tr>
                                </table>
                            @endif
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                <tr>
                                    <td align="center" style="padding-top: 8px;">
                                        <a href="{{ config('app.url') }}/dashboard" style="display: inline-block; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; font-size: 16px;">View Full Dashboard</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #6b7280;">&copy; {{ date('Y') }} Addy Business. All rights reserved.</p>
                            <p style="margin: 8px 0 0 0; font-size: 12px;">
                                <a href="{{ config('app.url') }}" style="color: #0d9488; text-decoration: none;">Visit Addy</a> &bull;
                                <a href="{{ config('app.url') }}/settings" style="color: #0d9488; text-decoration: none;">Manage Email Preferences</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
