<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $receipt->receipt_number }}</title>
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
        .receipt-number {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
            margin-bottom: 8px;
        }
        .receipt-title {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
        .summary-value-secondary {
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }
        .amount-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 32px;
            text-align: center;
        }
        .amount-label {
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        .amount-value {
            font-size: 36px;
            font-weight: 700;
            color: #111827;
        }
        .details-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
        }
        .detail-row:last-child {
            margin-bottom: 0;
        }
        .detail-label {
            font-size: 11px;
            color: #6b7280;
            font-weight: 500;
        }
        .detail-value {
            font-size: 11px;
            color: #111827;
            font-weight: 600;
            text-align: right;
        }
        .allocations-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .allocations-title {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .allocation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 11px;
        }
        .allocation-label {
            color: #6b7280;
        }
        .allocation-value {
            color: #111827;
            font-weight: 600;
        }
        .notes-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e5e7eb;
        }
        .notes-title {
            font-size: 11px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .notes-content {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
        }
        .footer {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .footer-message {
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 8px;
        }
        .footer-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }
        .footer-brand-text {
            font-size: 10px;
            color: #9ca3af;
        }
        .footer-brand-logo {
            height: 20px;
            width: auto;
            object-fit: contain;
        }
        .penda-footer {
            margin-top: 32px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
        }
        .penda-logo {
            height: 24px;
            width: auto;
            margin-bottom: 12px;
            object-fit: contain;
        }
        .penda-text {
            font-size: 9px;
            color: #6b7280;
            line-height: 1.5;
        }
        @page {
            margin: 0.5cm;
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
            <div class="receipt-title">Payment Receipt</div>
            <div class="receipt-number">{{ $receipt->receipt_number }}</div>
        </div>
    </div>

    <!-- Summary Section -->
    <div class="summary-section">
        <div class="summary-card">
            <div class="summary-label">Customer</div>
            <div class="summary-value">{{ $payment->customer->name ?? 'N/A' }}</div>
            @if($payment->customer && $payment->customer->email)
                <div class="summary-value-secondary">{{ $payment->customer->email }}</div>
            @endif
            @if($payment->customer && $payment->customer->phone)
                <div class="summary-value-secondary">{{ $payment->customer->phone }}</div>
            @endif
        </div>
        <div class="summary-card">
            <div class="summary-label">Receipt Date</div>
            <div class="summary-value">{{ \Carbon\Carbon::parse($receipt->receipt_date)->format('F d, Y') }}</div>
            <div class="summary-label" style="margin-top: 12px;">Payment Date</div>
            <div class="summary-value">{{ \Carbon\Carbon::parse($payment->payment_date)->format('F d, Y') }}</div>
        </div>
        <div class="summary-card" style="text-align: right;">
            <div class="summary-label">Payment Number</div>
            <div class="summary-value">{{ $payment->payment_number }}</div>
        </div>
    </div>

    <!-- Amount Section -->
    <div class="amount-section">
        <div class="amount-label">Amount Received</div>
        <div class="amount-value">{{ number_format($payment->amount, 2) }} {{ $payment->currency ?? 'ZMW' }}</div>
    </div>

    <!-- Payment Details -->
    <div class="details-section">
        <div class="detail-row">
            <span class="detail-label">Payment Method:</span>
            <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</span>
        </div>
        @if($payment->payment_reference)
            <div class="detail-row">
                <span class="detail-label">Reference:</span>
                <span class="detail-value">{{ $payment->payment_reference }}</span>
            </div>
        @endif
    </div>

    <!-- Allocations -->
    @if($payment->allocations && count($payment->allocations) > 0)
        <div class="allocations-section">
            <div class="allocations-title">Allocated to Invoices</div>
            @foreach($payment->allocations as $allocation)
                <div class="allocation-row">
                    <span class="allocation-label">Invoice {{ $allocation->invoice->invoice_number }}</span>
                    <span class="allocation-value">{{ number_format($allocation->amount, 2) }} {{ $payment->currency ?? 'ZMW' }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Notes -->
    @if($payment->notes)
        <div class="notes-section">
            <div class="notes-title">Payment Notes</div>
            <div class="notes-content">{{ $payment->notes }}</div>
        </div>
    @endif

    @if($receipt->notes)
        <div class="notes-section">
            <div class="notes-title">Receipt Notes</div>
            <div class="notes-content">{{ $receipt->notes }}</div>
        </div>
    @endif

    <!-- Footer -->
    <div class="footer">
        <div class="footer-message">Thank you for your payment!</div>
        <div class="footer-message">This is a computer-generated receipt.</div>
        
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

        <div class="penda-footer">
            @php
                $pendaLogoPath = public_path('assets/logos/penda.png');
                $pendaLogoUrl = file_exists($pendaLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($pendaLogoPath)) : '';
            @endphp
            @if($pendaLogoUrl)
                <img src="{{ $pendaLogoUrl }}" alt="Penda Digital" class="penda-logo" />
            @endif
            <div class="penda-text">
                <p>© {{ date('Y') }} All rights reserved.</p>
                <p>This is a product of Penda Digital, a registered company in the Republic of Zambia.</p>
                <p>Copyright © {{ date('Y') }} Penda Digital. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>

