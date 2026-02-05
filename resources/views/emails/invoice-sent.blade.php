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
                            <img src="{{ config('app.url') }}/assets/logos/icon-white.webp" alt="Addy" style="height: 48px; margin-bottom: 12px;">
                            <h1 style="margin: 0; font-size: 24px; font-weight: 700; color: white;">Invoice</h1>
                            <p style="margin: 8px 0 0; opacity: 0.9; font-size: 14px; color: white;">From {{ $organizationName }}</p>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 32px 24px;">
                            <p style="font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 16px 0;">Hi {{ $customerName }},</p>
                            
                            <p style="color: #4b5563; margin: 0 0 24px 0;">
                                Please find below the details of your invoice from <strong>{{ $organizationName }}</strong>.
                            </p>

                            @if($customMessage)
                            <!-- Custom Message -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background: #f0fdfa; border-left: 4px solid #0d9488; padding: 16px; border-radius: 0 8px 8px 0;">
                                        <p style="font-size: 12px; font-weight: 600; color: #0d9488; text-transform: uppercase; margin: 0 0 8px 0;">Message from {{ $organizationName }}</p>
                                        <p style="color: #374151; font-size: 14px; margin: 0;">{{ $customMessage }}</p>
                                    </td>
                                </tr>
                            </table>
                            @endif
                            
                            <!-- Invoice Card -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 24px;">
                                <!-- Invoice Header -->
                                <tr>
                                    <td style="padding: 20px 20px 16px 20px; border-bottom: 1px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="font-size: 18px; font-weight: 700; color: #111827;">{{ $invoiceNumber }}</td>
                                                <td align="right">
                                                    <span style="display: inline-block; background: #ccfbf1; color: #0d9488; padding: 4px 12px; border-radius: 9999px; font-size: 12px; font-weight: 600; text-transform: uppercase;">Unpaid</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                
                                <!-- Invoice Details -->
                                <tr>
                                    <td style="padding: 16px 20px;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td width="50%" valign="top" style="padding-right: 10px;">
                                                    <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 4px 0;">Invoice Date</p>
                                                    <p style="font-size: 15px; font-weight: 500; color: #111827; margin: 0;">{{ $invoiceDate }}</p>
                                                </td>
                                                <td width="50%" valign="top" style="padding-left: 10px;">
                                                    <p style="font-size: 11px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin: 0 0 4px 0;">Due Date</p>
                                                    <p style="font-size: 15px; font-weight: 500; color: #111827; margin: 0;">{{ $dueDate }}</p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                
                                <!-- Total -->
                                <tr>
                                    <td style="padding: 16px 20px 20px 20px; border-top: 2px solid #e5e7eb;">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                                            <tr>
                                                <td style="font-size: 14px; font-weight: 600; color: #6b7280;">Total Amount</td>
                                                <td align="right" style="font-size: 24px; font-weight: 700; color: #0d9488;">{{ $currency }} {{ $totalAmount }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- CTA Button -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $viewUrl }}" style="display: inline-block; background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%); color: white; text-decoration: none; padding: 14px 32px; border-radius: 10px; font-weight: 600; font-size: 16px;">View Invoice</a>
                                    </td>
                                </tr>
                            </table>
                            
                            <!-- Payment Terms Note -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-bottom: 24px;">
                                <tr>
                                    <td style="background: #fef3c7; color: #92400e; padding: 12px 16px; border-radius: 8px; font-size: 13px;">
                                        <strong>Payment Terms:</strong> Please ensure payment is made by the due date to avoid any late fees. If you have any questions about this invoice, please contact {{ $organizationName }} directly.
                                    </td>
                                </tr>
                            </table>

                            <!-- From Business -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top: 1px solid #e5e7eb; padding-top: 24px;">
                                <tr>
                                    <td style="padding-top: 24px;">
                                        <p style="font-size: 12px; color: #6b7280; text-transform: uppercase; margin: 0 0 4px 0;">This invoice was sent by</p>
                                        <p style="font-size: 16px; font-weight: 600; color: #111827; margin: 0;">{{ $organizationName }}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #f9fafb; padding: 24px; text-align: center; border-top: 1px solid #e5e7eb;">
                            <p style="margin: 0; font-size: 12px; color: #6b7280;">Powered by Addy Business</p>
                            <p style="margin: 8px 0 0 0; font-size: 12px;">
                                <a href="{{ config('app.url') }}" style="color: #0d9488; text-decoration: none;">Learn more about Addy</a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
