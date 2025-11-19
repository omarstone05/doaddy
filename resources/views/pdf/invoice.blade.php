<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            color: #111827;
            line-height: 1.5;
            padding: 40px;
            background: #fff;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 40px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .header-left {
            flex: 1;
        }
        .header-left .logo {
            max-height: 60px;
            max-width: 200px;
            margin-bottom: 12px;
            object-fit: contain;
        }
        .header-left .org-name {
            font-size: 14px;
            font-weight: 600;
            color: #111827;
            margin-top: 8px;
        }
        .header-right {
            text-align: right;
        }
        .invoice-number {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 10px;
            font-weight: 600;
            text-transform: capitalize;
        }
        .status-draft {
            background-color: #f3f4f6;
            color: #6b7280;
        }
        .status-sent {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .status-paid {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-overdue {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-partial {
            background-color: #fef3c7;
            color: #92400e;
        }
        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .summary-card {
            flex: 1;
            margin-right: 24px;
        }
        .summary-card:last-child {
            margin-right: 0;
        }
        .summary-label {
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .summary-value {
            font-size: 13px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .summary-value-large {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
        }
        .summary-value-secondary {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .summary-value-success {
            color: #059669;
        }
        .summary-value-danger {
            color: #dc2626;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
        }
        thead {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }
        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        th.text-right {
            text-align: right;
        }
        td {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
            color: #111827;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .item-name {
            font-weight: 600;
            color: #111827;
            margin-bottom: 4px;
        }
        .item-description {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }
        .totals-section {
            margin-top: 24px;
            margin-left: auto;
            width: 320px;
            padding-top: 16px;
            border-top: 1px solid #e5e7eb;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            font-size: 12px;
        }
        .totals-row-label {
            color: #6b7280;
        }
        .totals-row-value {
            font-weight: 600;
            color: #111827;
        }
        .totals-row.total {
            font-size: 16px;
            font-weight: 700;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 2px solid #e5e7eb;
        }
        .totals-row.outstanding {
            color: #dc2626;
            font-weight: 700;
            font-size: 14px;
            padding-top: 12px;
            margin-top: 12px;
            border-top: 2px solid #fee2e2;
        }
        .totals-row.paid {
            color: #059669;
        }
        .notes-section {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .notes-title {
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .notes-content {
            color: #111827;
            font-size: 11px;
            line-height: 1.6;
        }
        .bank-details {
            margin-top: 32px;
            padding: 16px;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 6px;
        }
        .bank-details h3 {
            font-size: 10px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
        }
        .bank-details-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 24px;
        }
        .bank-item {
            flex: 1;
            min-width: 180px;
        }
        .bank-item strong {
            display: block;
            color: #111827;
            font-size: 10px;
            font-weight: 600;
            margin-bottom: 4px;
        }
        .bank-item p {
            margin: 0;
            color: #6b7280;
            font-size: 10px;
        }
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 9px;
            color: #9ca3af;
        }
        .footer-brand {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            margin-top: 8px;
        }
        .footer-brand-text {
            color: #6b7280;
        }
        .footer-brand-logo {
            height: 20px;
            width: 20px;
            object-fit: contain;
        }
        @media print {
            body {
                padding: 20px;
            }
            .footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                margin-top: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            @if(isset($logoUrl) && $logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $organization->name ?? 'Logo' }}" class="logo" />
            @endif
            <div class="org-name">{{ $organization->name ?? 'Addy Business' }}</div>
        </div>
        <div class="header-right">
            <div class="invoice-number">{{ $invoice->invoice_number }}</div>
            <span class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-card">
            <div class="summary-label">Customer</div>
            <div class="summary-value">{{ $invoice->customer->name }}</div>
            @if($invoice->customer->email)
                <div class="summary-value-secondary">{{ $invoice->customer->email }}</div>
            @endif
            @if($invoice->customer->phone)
                <div class="summary-value-secondary">{{ $invoice->customer->phone }}</div>
            @endif
        </div>
        <div class="summary-card">
            <div class="summary-label">Invoice Date</div>
            <div class="summary-value">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('F d, Y') }}</div>
            @if($invoice->due_date)
                <div class="summary-label" style="margin-top: 12px;">Due Date</div>
                <div class="summary-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}</div>
            @endif
        </div>
        <div class="summary-card" style="text-align: right;">
            <div class="summary-label">Amount</div>
            <div class="summary-value-large">{{ number_format($invoice->total_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</div>
            @php
                $outstanding = $invoice->total_amount - ($invoice->paid_amount ?? 0);
            @endphp
            @if($invoice->paid_amount > 0)
                <div class="summary-value-secondary summary-value-success" style="margin-top: 4px;">
                    Paid: {{ number_format($invoice->paid_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}
                </div>
            @endif
            @if($outstanding > 0)
                <div class="summary-value-secondary summary-value-danger" style="margin-top: 4px;">
                    Outstanding: {{ number_format($outstanding, 2) }} {{ $organization->currency ?? 'ZMW' }}
                </div>
            @endif
        </div>
    </div>

    <!-- Items Table -->
    <table>
        <thead>
            <tr>
                <th>Product / Service</th>
                <th>Description</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>
                        <div class="item-name">{{ $item->name }}</div>
                    </td>
                    <td>
                        @if($item->description)
                            <div class="item-description">{{ $item->description }}</div>
                        @else
                            <div class="item-description" style="color: #9ca3af;">—</div>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }} {{ $organization->currency ?? 'ZMW' }}</td>
                    <td class="text-right"><strong>{{ number_format($item->total, 2) }} {{ $organization->currency ?? 'ZMW' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-row">
            <span class="totals-row-label">Subtotal:</span>
            <span class="totals-row-value">{{ number_format($invoice->subtotal, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
        </div>
        @if($invoice->tax_amount > 0)
            <div class="totals-row">
                <span class="totals-row-label">Tax:</span>
                <span class="totals-row-value">{{ number_format($invoice->tax_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
        @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span class="totals-row-label">Discount:</span>
                <span class="totals-row-value" style="color: #dc2626;">-{{ number_format($invoice->discount_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
        <div class="totals-row total">
            <span>Total:</span>
            <span>{{ number_format($invoice->total_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
        </div>
        @if($invoice->paid_amount > 0)
            <div class="totals-row paid">
                <span class="totals-row-label">Paid:</span>
                <span class="totals-row-value">{{ number_format($invoice->paid_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
        @php
            $outstanding = $invoice->total_amount - ($invoice->paid_amount ?? 0);
        @endphp
        @if($outstanding > 0)
            <div class="totals-row outstanding">
                <span>Outstanding:</span>
                <span>{{ number_format($outstanding, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
    </div>

    <!-- Notes -->
    @if($invoice->notes)
        <div class="notes-section">
            <div class="notes-title">Notes</div>
            <div class="notes-content">{{ $invoice->notes }}</div>
        </div>
    @endif

    <!-- Terms -->
    @if($invoice->terms)
        <div class="notes-section">
            <div class="notes-title">Terms & Conditions</div>
            <div class="notes-content">{{ $invoice->terms }}</div>
        </div>
    @endif

    <!-- Bank Details -->
    @if(isset($bankDetails) && $bankDetails)
        <div class="bank-details">
            <h3>Payment Information</h3>
            <div class="bank-details-grid">
                @if(isset($bankDetails['bank_name']) && $bankDetails['bank_name'])
                    <div class="bank-item">
                        <strong>Bank Name:</strong>
                        <p>{{ $bankDetails['bank_name'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['account_name']) && $bankDetails['account_name'])
                    <div class="bank-item">
                        <strong>Account Name:</strong>
                        <p>{{ $bankDetails['account_name'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['account_number']) && $bankDetails['account_number'])
                    <div class="bank-item">
                        <strong>Account Number:</strong>
                        <p>{{ $bankDetails['account_number'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['branch']) && $bankDetails['branch'])
                    <div class="bank-item">
                        <strong>Branch:</strong>
                        <p>{{ $bankDetails['branch'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['swift_code']) && $bankDetails['swift_code'])
                    <div class="bank-item">
                        <strong>SWIFT Code:</strong>
                        <p>{{ $bankDetails['swift_code'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['mobile_money']) && $bankDetails['mobile_money'])
                    <div class="bank-item">
                        <strong>Mobile Money:</strong>
                        <p>{{ $bankDetails['mobile_money'] }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['payment_options']) && is_array($bankDetails['payment_options']) && count($bankDetails['payment_options']) > 0)
                    <div class="bank-item" style="flex-basis: 100%;">
                        <strong>Payment Options:</strong>
                        <p>{{ implode(', ', $bankDetails['payment_options']) }}</p>
                    </div>
                @endif
                @if(isset($bankDetails['notes']) && $bankDetails['notes'])
                    <div class="bank-item" style="flex-basis: 100%; margin-top: 8px;">
                        <p style="font-size: 9px; color: #6b7280; font-style: italic;">{{ $bankDetails['notes'] }}</p>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="footer-brand">
            <span class="footer-brand-text">Created with</span>
            @php
                $logoPath = public_path('assets/logos/icon.png');
                $addyLogoUrl = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : '';
            @endphp
            @if($addyLogoUrl)
                <img src="{{ $addyLogoUrl }}" alt="Addy" class="footer-brand-logo" />
            @endif
        </div>
    </div>
</body>
</html>
