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
            font-size: 12px;
            color: #333;
            line-height: 1.6;
            padding: 40px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #14b8a6;
        }
        .header-left h1 {
            font-size: 28px;
            color: #0d9488;
            margin-bottom: 5px;
        }
        .header-left p {
            color: #666;
            font-size: 11px;
        }
        .header-right {
            text-align: right;
        }
        .header-right h2 {
            font-size: 20px;
            color: #333;
            margin-bottom: 5px;
        }
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .info-box {
            flex: 1;
            margin-right: 20px;
        }
        .info-box:last-child {
            margin-right: 0;
        }
        .info-box h3 {
            font-size: 10px;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .info-box p {
            margin: 3px 0;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: #f0fdfa;
            border-bottom: 2px solid #14b8a6;
        }
        th {
            padding: 10px;
            text-align: left;
            font-size: 10px;
            text-transform: uppercase;
            color: #0d9488;
            font-weight: 600;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e5e7eb;
        }
        tbody tr:last-child td {
            border-bottom: none;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .totals-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 12px;
        }
        .totals-row.total {
            font-size: 16px;
            font-weight: bold;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 2px solid #14b8a6;
        }
        .totals-row.outstanding {
            color: #dc2626;
            font-weight: bold;
            font-size: 14px;
            padding-top: 10px;
            margin-top: 10px;
            border-top: 2px solid #fecaca;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #666;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
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
    </style>
</head>
<body>
    <div class="header">
        <div class="header-left">
            @if(isset($logoUrl) && $logoUrl)
                <img src="{{ $logoUrl }}" alt="{{ $organization->name ?? 'Logo' }}" style="max-height: 60px; max-width: 200px; margin-bottom: 10px; object-fit: contain;" />
            @else
                <h1>INVOICE</h1>
            @endif
            <p>{{ $organization->name ?? 'Addy Business' }}</p>
        </div>
        <div class="header-right">
            <h2>{{ $invoice->invoice_number }}</h2>
            <span class="status-badge status-{{ $invoice->status }}">
                {{ ucfirst($invoice->status) }}
            </span>
        </div>
    </div>

    <div class="info-section">
        <div class="info-box">
            <h3>Bill To</h3>
            <p><strong>{{ $invoice->customer->name }}</strong></p>
            @if($invoice->customer->email)
                <p>{{ $invoice->customer->email }}</p>
            @endif
            @if($invoice->customer->phone)
                <p>{{ $invoice->customer->phone }}</p>
            @endif
            @if($invoice->customer->address)
                <p>{{ $invoice->customer->address }}</p>
            @endif
        </div>
        <div class="info-box">
            <h3>Invoice Details</h3>
            <p><strong>Invoice Date:</strong> {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('F d, Y') }}</p>
            @if($invoice->due_date)
                <p><strong>Due Date:</strong> {{ \Carbon\Carbon::parse($invoice->due_date)->format('F d, Y') }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th class="text-right">Quantity</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="text-right">{{ number_format($item->quantity, 2) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 2) }} {{ $organization->currency ?? 'ZMW' }}</td>
                    <td class="text-right"><strong>{{ number_format($item->total, 2) }} {{ $organization->currency ?? 'ZMW' }}</strong></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>{{ number_format($invoice->subtotal, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
        </div>
        @if($invoice->tax_amount > 0)
            <div class="totals-row">
                <span>Tax:</span>
                <span>{{ number_format($invoice->tax_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
        @if($invoice->discount_amount > 0)
            <div class="totals-row">
                <span>Discount:</span>
                <span>-{{ number_format($invoice->discount_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
            </div>
        @endif
        <div class="totals-row total">
            <span>Total:</span>
            <span>{{ number_format($invoice->total_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
        </div>
        @if($invoice->paid_amount > 0)
            <div class="totals-row">
                <span>Paid:</span>
                <span style="color: #059669;">{{ number_format($invoice->paid_amount, 2) }} {{ $organization->currency ?? 'ZMW' }}</span>
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

    @if($invoice->notes)
        <div style="margin-top: 30px;">
            <h3 style="font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 8px;">Notes</h3>
            <p style="color: #333;">{{ $invoice->notes }}</p>
        </div>
    @endif

    @if($invoice->terms)
        <div style="margin-top: 20px;">
            <h3 style="font-size: 10px; text-transform: uppercase; color: #666; margin-bottom: 8px;">Terms & Conditions</h3>
            <p style="color: #333;">{{ $invoice->terms }}</p>
        </div>
    @endif

    <div class="footer" style="margin-top: 50px; padding-top: 20px; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: center; gap: 8px;">
        <p style="font-size: 10px; color: #666; margin: 0;">Generated by</p>
        <img src="{{ asset('assets/logos/icon.png') }}" alt="Addy" style="height: 16px; width: 16px; object-fit: contain;" />
        <p style="font-size: 10px; color: #0d9488; font-weight: 600; margin: 0;">Addy</p>
        <p style="font-size: 10px; color: #666; margin: 0;">on {{ now()->format('F d, Y \a\t g:i A') }}</p>
    </div>
</body>
</html>

