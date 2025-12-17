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
            color: #334155;
            line-height: 1.6;
            background: #fff;
            padding: 0;
        }
        .container {
            padding: 48px;
            max-width: 100%;
        }
        
        /* Header Section */
        .header {
            margin-bottom: 40px;
        }
        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 32px;
        }
        .header-left {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            text-align: right;
        }
        .logo {
            max-height: 56px;
            max-width: 180px;
            margin-bottom: 16px;
        }
        .org-details {
            color: #64748b;
            font-size: 10px;
            line-height: 1.7;
        }
        .doc-title {
            font-size: 32px;
            font-weight: 700;
            color: #0f766e;
            letter-spacing: -0.5px;
            margin-bottom: 4px;
        }
        .doc-date {
            font-size: 11px;
            color: #64748b;
        }
        
        /* Info Grid */
        .info-grid {
            display: table;
            width: 100%;
            margin-bottom: 32px;
        }
        .info-col {
            display: table-cell;
            width: 33.33%;
            vertical-align: top;
            padding-right: 24px;
        }
        .info-col:last-child {
            padding-right: 0;
        }
        .info-label {
            font-size: 9px;
            font-weight: 600;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .info-value {
            font-size: 12px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .info-secondary {
            font-size: 10px;
            color: #64748b;
            line-height: 1.5;
        }
        
        /* Total Amount Box */
        .total-box {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            color: white;
            padding: 16px 24px;
            border-radius: 8px;
            text-align: center;
        }
        .total-box.has-outstanding {
            background: linear-gradient(135deg, #dc2626 0%, #f87171 100%);
        }
        .total-label {
            font-size: 9px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            opacity: 0.9;
            margin-bottom: 4px;
        }
        .total-amount {
            font-size: 22px;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        .total-secondary {
            font-size: 9px;
            opacity: 0.85;
            margin-top: 4px;
        }
        
        /* Status Badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 8px;
        }
        .status-draft {
            background-color: #f1f5f9;
            color: #64748b;
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
        
        /* Table */
        .items-section {
            margin-bottom: 32px;
        }
        .section-title {
            font-size: 9px;
            font-weight: 600;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 16px;
            padding-bottom: 8px;
            border-bottom: 2px solid #e2e8f0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        thead tr {
            background-color: #f8fafc;
        }
        th {
            padding: 12px 16px;
            text-align: left;
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid #e2e8f0;
        }
        th.text-right {
            text-align: right;
        }
        th.text-center {
            text-align: center;
        }
        td {
            padding: 14px 16px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .item-name {
            font-weight: 600;
            color: #1e293b;
            font-size: 11px;
            margin-bottom: 2px;
        }
        .item-description {
            font-size: 10px;
            color: #94a3b8;
        }
        .item-price {
            font-weight: 500;
            color: #475569;
        }
        .item-total {
            font-weight: 700;
            color: #0f766e;
        }
        
        /* Totals Section */
        .totals-wrapper {
            display: table;
            width: 100%;
        }
        .totals-spacer {
            display: table-cell;
            width: 55%;
        }
        .totals-section {
            display: table-cell;
            width: 45%;
        }
        .totals-inner {
            background-color: #f8fafc;
            border-radius: 8px;
            padding: 20px 24px;
        }
        .totals-row {
            display: table;
            width: 100%;
            margin-bottom: 8px;
        }
        .totals-row:last-child {
            margin-bottom: 0;
        }
        .totals-label {
            display: table-cell;
            font-size: 11px;
            color: #64748b;
            padding: 4px 0;
        }
        .totals-value {
            display: table-cell;
            text-align: right;
            font-size: 11px;
            font-weight: 600;
            color: #1e293b;
            padding: 4px 0;
        }
        .totals-row.grand-total {
            border-top: 2px solid #e2e8f0;
            margin-top: 12px;
            padding-top: 12px;
        }
        .totals-row.grand-total .totals-label {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .totals-row.grand-total .totals-value {
            font-size: 16px;
            font-weight: 700;
            color: #0f766e;
        }
        .totals-row.paid .totals-value {
            color: #059669;
        }
        .totals-row.outstanding {
            margin-top: 8px;
        }
        .totals-row.outstanding .totals-label {
            font-weight: 600;
            color: #dc2626;
        }
        .totals-row.outstanding .totals-value {
            font-weight: 700;
            color: #dc2626;
        }
        
        /* Bank Details */
        .bank-section {
            margin-top: 32px;
            padding: 20px 24px;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }
        .bank-title {
            font-size: 9px;
            font-weight: 600;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 16px;
        }
        .bank-grid {
            display: table;
            width: 100%;
        }
        .bank-item {
            display: table-cell;
            width: 25%;
            padding-right: 16px;
            vertical-align: top;
        }
        .bank-item:last-child {
            padding-right: 0;
        }
        .bank-item-label {
            font-size: 9px;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 4px;
        }
        .bank-item-value {
            font-size: 10px;
            color: #1e293b;
        }
        
        /* Notes Section */
        .notes-section {
            margin-top: 40px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
        }
        .notes-grid {
            display: table;
            width: 100%;
        }
        .notes-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 32px;
        }
        .notes-col:last-child {
            padding-right: 0;
        }
        .notes-title {
            font-size: 9px;
            font-weight: 600;
            color: #0f766e;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 8px;
        }
        .notes-content {
            font-size: 10px;
            color: #64748b;
            line-height: 1.7;
        }
        
        /* Footer */
        .footer {
            margin-top: 60px;
            padding-top: 24px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
        }
        .footer-text {
            font-size: 10px;
            color: #94a3b8;
            margin-bottom: 12px;
        }
        .footer-brand {
            display: inline-block;
        }
        .footer-brand-inner {
            display: table;
        }
        .footer-powered {
            display: table-cell;
            vertical-align: middle;
            font-size: 9px;
            color: #94a3b8;
            padding-right: 8px;
        }
        .footer-logo {
            display: table-cell;
            vertical-align: middle;
        }
        .footer-logo img {
            height: 22px;
            width: auto;
        }
        
        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .container {
                padding: 32px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-top">
                <div class="header-left">
                    @if(isset($logoUrl) && $logoUrl)
                        <img src="{{ $logoUrl }}" alt="{{ $organization->name ?? 'Company' }}" class="logo" />
                    @endif
                    <div class="org-details">
                        <strong style="color: #1e293b; font-size: 13px;">{{ $organization->name ?? 'Your Business' }}</strong><br>
                        @if($organization->address ?? null){{ $organization->address }}<br>@endif
                        @if($organization->phone ?? null){{ $organization->phone }}<br>@endif
                        @if($organization->email ?? null){{ $organization->email }}@endif
                    </div>
                </div>
                <div class="header-right">
                    <div class="doc-title">INVOICE</div>
                    <div class="doc-date">{{ \Carbon\Carbon::parse($invoice->invoice_date)->format('F d, Y') }}</div>
                </div>
            </div>
        </div>
        
        <!-- Info Grid -->
        @php
            $outstanding = $invoice->total_amount - ($invoice->paid_amount ?? 0);
        @endphp
        <div class="info-grid">
            <div class="info-col">
                <div class="info-label">Bill To</div>
                <div class="info-value">{{ $invoice->customer->name }}</div>
                <div class="info-secondary">
                    @if($invoice->customer->email){{ $invoice->customer->email }}<br>@endif
                    @if($invoice->customer->phone){{ $invoice->customer->phone }}@endif
                </div>
            </div>
            <div class="info-col">
                <div class="info-label">Invoice Details</div>
                <div class="info-secondary">
                    <strong style="color: #1e293b;">{{ $invoice->invoice_number }}</strong><br>
                    Issue: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') }}<br>
                    @if($invoice->due_date)Due: {{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}@endif
                </div>
                <span class="status-badge status-{{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span>
            </div>
            <div class="info-col">
                <div class="total-box{{ $outstanding > 0 && $invoice->status !== 'paid' ? ' has-outstanding' : '' }}">
                    <div class="total-label">{{ $outstanding > 0 && $invoice->status !== 'paid' ? 'Amount Due' : 'Total Amount' }}</div>
                    <div class="total-amount">{{ number_format($outstanding > 0 && $invoice->status !== 'paid' ? $outstanding : $invoice->total_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</div>
                    @if($invoice->paid_amount > 0 && $outstanding > 0)
                        <div class="total-secondary">Paid: {{ number_format($invoice->paid_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <div class="items-section">
            <div class="section-title">Items</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 35%;">Description</th>
                        <th style="width: 20%;">Details</th>
                        <th class="text-center" style="width: 10%;">Qty</th>
                        <th class="text-right" style="width: 17.5%;">Unit Price</th>
                        <th class="text-right" style="width: 17.5%;">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoice->items as $item)
                        <tr>
                            <td>
                                <div class="item-name">{{ $item->name }}</div>
                            </td>
                            <td>
                                @if($item->description)
                                    <div class="item-description">{{ $item->description }}</div>
                                @else
                                    <div class="item-description">—</div>
                                @endif
                            </td>
                            <td class="text-center">{{ number_format($item->quantity, 0) }}</td>
                            <td class="text-right item-price">{{ number_format($item->unit_price, 2) }}</td>
                            <td class="text-right item-total">{{ number_format($item->total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 32px;">No items</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Totals -->
        <div class="totals-wrapper">
            <div class="totals-spacer"></div>
            <div class="totals-section">
                <div class="totals-inner">
                    <div class="totals-row">
                        <span class="totals-label">Subtotal</span>
                        <span class="totals-value">{{ number_format($invoice->subtotal, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                    </div>
                    @if($invoice->tax_amount > 0)
                        <div class="totals-row">
                            <span class="totals-label">Tax</span>
                            <span class="totals-value">{{ number_format($invoice->tax_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                        </div>
                    @endif
                    @if($invoice->discount_amount > 0)
                        <div class="totals-row">
                            <span class="totals-label">Discount</span>
                            <span class="totals-value" style="color: #dc2626;">-{{ number_format($invoice->discount_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                        </div>
                    @endif
                    <div class="totals-row grand-total">
                        <span class="totals-label">Total</span>
                        <span class="totals-value">{{ number_format($invoice->total_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                    </div>
                    @if($invoice->paid_amount > 0)
                        <div class="totals-row paid">
                            <span class="totals-label">Paid</span>
                            <span class="totals-value">{{ number_format($invoice->paid_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                        </div>
                    @endif
                    @if($outstanding > 0 && $invoice->status !== 'paid')
                        <div class="totals-row outstanding">
                            <span class="totals-label">Outstanding</span>
                            <span class="totals-value">{{ number_format($outstanding, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Bank Details -->
        @if(isset($bankDetails) && $bankDetails && (($bankDetails['bank_name'] ?? null) || ($bankDetails['account_number'] ?? null) || ($bankDetails['mobile_money'] ?? null)))
            <div class="bank-section">
                <div class="bank-title">Payment Information</div>
                <div class="bank-grid">
                    @if(isset($bankDetails['bank_name']) && $bankDetails['bank_name'])
                        <div class="bank-item">
                            <div class="bank-item-label">Bank Name</div>
                            <div class="bank-item-value">{{ $bankDetails['bank_name'] }}</div>
                        </div>
                    @endif
                    @if(isset($bankDetails['account_name']) && $bankDetails['account_name'])
                        <div class="bank-item">
                            <div class="bank-item-label">Account Name</div>
                            <div class="bank-item-value">{{ $bankDetails['account_name'] }}</div>
                        </div>
                    @endif
                    @if(isset($bankDetails['account_number']) && $bankDetails['account_number'])
                        <div class="bank-item">
                            <div class="bank-item-label">Account Number</div>
                            <div class="bank-item-value">{{ $bankDetails['account_number'] }}</div>
                        </div>
                    @endif
                    @if(isset($bankDetails['branch']) && $bankDetails['branch'])
                        <div class="bank-item">
                            <div class="bank-item-label">Branch</div>
                            <div class="bank-item-value">{{ $bankDetails['branch'] }}</div>
                        </div>
                    @endif
                </div>
                @if(isset($bankDetails['mobile_money']) && $bankDetails['mobile_money'])
                    <div style="margin-top: 12px;">
                        <div class="bank-item-label">Mobile Money</div>
                        <div class="bank-item-value">{{ $bankDetails['mobile_money'] }}</div>
                    </div>
                @endif
            </div>
        @endif
        
        <!-- Notes & Terms -->
        @if($invoice->notes || $invoice->terms)
            <div class="notes-section">
                <div class="notes-grid">
                    @if($invoice->notes)
                        <div class="notes-col">
                            <div class="notes-title">Notes</div>
                            <div class="notes-content">{{ $invoice->notes }}</div>
                        </div>
                    @endif
                    @if($invoice->terms)
                        <div class="notes-col">
                            <div class="notes-title">Terms & Conditions</div>
                            <div class="notes-content">{{ $invoice->terms }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
        
        <!-- Footer -->
        <div class="footer">
            <div class="footer-text">Thank you for your business!</div>
            <div class="footer-brand">
                <div class="footer-brand-inner">
                    <span class="footer-powered">Powered by</span>
                    <span class="footer-logo">
                        @php
                            $addyLogoPath = public_path('assets/logos/size.png');
                            $addyLogoUrl = file_exists($addyLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($addyLogoPath)) : '';
                        @endphp
                        @if($addyLogoUrl)
                            <img src="{{ $addyLogoUrl }}" alt="Addy" />
                        @else
                            <span style="color: #0f766e; font-weight: 700;">Addy</span>
                        @endif
                    </span>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
